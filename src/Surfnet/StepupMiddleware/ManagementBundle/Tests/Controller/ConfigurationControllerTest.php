<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddleware\ManagementBundle\Tests\Controller;

use Broadway\EventStore\Dbal\DBALEventStore;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private KernelBrowser $client;

    private string $password;

    private string $passwordRo;

    private AbstractDatabaseTool $databaseTool;

    private Connection $connection;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class);
        if (!$databaseTool instanceof DatabaseToolCollection) {
            $this->fail('Unable to grab the ORMSqliteDatabaseTool from the container');
        }
        $this->databaseTool = $databaseTool->get();
        // Initialises schema.
        $this->databaseTool->setExcludedDoctrineTables(['ra_candidate']);
        $this->databaseTool->loadFixtures([]);

        $connection = $this->client->getContainer()->get('doctrine.dbal.middleware_connection');
        assert($connection instanceof Connection);
        $this->connection = $connection;

        // Broadway event store table is not managed by Doctrine ORM; create it manually for the test DB.
        $eventStore = $this->client->getContainer()->get('surfnet_stepup.event_store.dbal');
        assert($eventStore instanceof DBALEventStore);
        $schemaManager = $this->connection->createSchemaManager();
        $table = $eventStore->configureTable();
        if (!$schemaManager->tablesExist(['event_stream'])) {
            $schemaManager->createTable($table);
        }

        // Gateway entity manager schema (saml_entity etc.) is a separate SQLite DB in test env.
        $gatewayEm = $this->client->getContainer()->get('doctrine.orm.gateway_entity_manager');
        assert($gatewayEm instanceof EntityManagerInterface);
        $schemaTool = new SchemaTool($gatewayEm);
        $schemaTool->updateSchema($gatewayEm->getMetadataFactory()->getAllMetadata());

        $managementPassword = $this->client->getKernel()->getContainer()->getParameter('management_password');
        if (!is_string($managementPassword)) {
            $this->fail('Unable to grab the management_password parameter from the container');
        }
        $this->password = $managementPassword;

        $readOnlyPassword = $this->client->getKernel()->getContainer()->getParameter('readonly_api_password');
        if (!is_string($readOnlyPassword)) {
            $this->fail('Unable to grab the readonly_api_password parameter from the container');
        }
        $this->passwordRo = $readOnlyPassword;
    }

    public function tearDown(): void
    {
        static::ensureKernelShutdown();
    }

    #[Test]
    #[Group('management')]
    public function requests_with_invalid_content_are_bad_requests(): void
    {
        $this->client->request(
            'POST',
            '/management/configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'management',
                'PHP_AUTH_PW' => $this->password,
            ],
            '[]',
        );

        $this->assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->client->getResponse()->getStatusCode(),
            (string) $this->client->getResponse()->getContent(),
        );
    }

    #[Test]
    #[Group('management')]
    public function authorization_is_required(): void
    {
        $this->client->request(
            'POST',
            '/management/configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            '[]',
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('management')]
    public function readonly_user_cannot_modify_configuration(): void
    {
        $this->client->request(
            'POST',
            '/management/configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'apireader',
                'PHP_AUTH_PW' => $this->passwordRo,
            ],
            '[]',
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[DataProvider('invalidHttpMethodProvider')]
    #[Group('management')]
    public function only_post_requests_are_accepted(string $invalidHttpMethod): void
    {
        $this->client->request(
            $invalidHttpMethod,
            '/management/configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            '[]',
        );

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('management')]
    public function json_is_returned_from_the_configuration_api(): void
    {
        $this->client->request(
            'POST',
            '/management/configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'management',
                'PHP_AUTH_PW' => $this->password,
            ],
            '[]',
        );

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json',
            ),
        );
    }

    #[Test]
    #[Group('management')]
    public function validPushWithOnlyGatewayIsAccepted(): void
    {
        $this->pushConfiguration([
            'gateway' => [
                'identity_providers' => [],
                'service_providers' => [self::minimalServiceProvider()],
            ],
        ]);
    }

    #[Test]
    #[Group('management')]
    public function pushWithSraaAndEmailTemplatesIsSilentlyAccepted(): void
    {
        $this->pushConfiguration([
            'gateway' => [
                'identity_providers' => [],
                'service_providers' => [self::minimalServiceProvider()],
            ],
            'sraa' => ['urn:collab:person:example.com:admin'],
            'email_templates' => [
                'confirm_email' => ['en_GB' => 'Verify {{ commonName }}'],
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
        assert(is_string($content));
        $response = json_decode($content, true);
        assert(is_array($response));
        $this->assertEquals('OK', $response['status']);
    }

    #[Test]
    #[Group('management')]
    public function pushing_configuration_with_reordered_service_providers_does_not_emit_new_events(): void
    {
        $spA = array_merge(self::minimalServiceProvider(), ['entity_id' => 'https://sp-a.example.com/metadata']);
        $spB = array_merge(self::minimalServiceProvider(), ['entity_id' => 'https://sp-b.example.com/metadata']);

        $this->pushConfiguration(['gateway' => ['identity_providers' => [], 'service_providers' => [$spA, $spB]]]);
        $eventCountAfterFirstPush = $this->getEventStreamCount();

        $this->pushConfiguration(['gateway' => ['identity_providers' => [], 'service_providers' => [$spB, $spA]]]);

        $this->assertSame(
            $eventCountAfterFirstPush,
            $this->getEventStreamCount(),
            'Reordering service providers should not emit new events',
        );
    }

    /** @param array<mixed> $configuration */
    private function pushConfiguration(array $configuration): void
    {
        $encoded = json_encode($configuration);
        assert(is_string($encoded));

        $this->client->request('POST', '/management/configuration', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'PHP_AUTH_USER' => 'management',
            'PHP_AUTH_PW' => $this->password,
        ], $encoded);

        $this->assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            (string) $this->client->getResponse()->getContent(),
        );
    }

    private function getEventStreamCount(): int
    {
        $result = $this->connection->fetchOne('SELECT COUNT(*) FROM event_stream');
        assert(is_string($result) || is_int($result));
        return (int) $result;
    }

    /** @return array<string, mixed> */
    private static function minimalServiceProvider(): array
    {
        return [
            'entity_id' => 'https://sp.example.com/metadata',
            'public_key' => 'MIIE...',
            'acs' => ['https://sp.example.com/acs'],
            'loa' => ['__default__' => 'https://gateway.example.com/authentication/loa2'],
            'second_factor_only' => false,
            'second_factor_only_nameid_patterns' => [],
            'assertion_encryption_enabled' => false,
            'blacklisted_encryption_algorithms' => [],
        ];
    }

    /**
     * Dataprovider for only_post_requests_are_accepted
     */
    public static function invalidHttpMethodProvider(): array
    {
        return [
            'GET' => ['GET'],
            'DELETE' => ['DELETE'],
            'HEAD' => ['HEAD'],
            'PUT' => ['PUT'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }
}

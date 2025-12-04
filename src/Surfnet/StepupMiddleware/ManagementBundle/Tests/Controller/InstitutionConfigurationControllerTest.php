<?php

/**
 * Copyright 2016 SURFnet B.V.
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

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use function is_string;

class InstitutionConfigurationControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private KernelBrowser $client;

    private string $password;

    private string $passwordRo;

    private AbstractDatabaseTool $databaseTool;

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
    public function authorization_is_required_for_reconfiguring_institution_configuration_options(): void
    {
        $this->client->request(
            'POST',
            '/management/institution-configuration',
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
    public function readonly_user_cannot_reconfigure_institution_configuration_options(): void
    {
        $this->client->request(
            'POST',
            '/management/institution-configuration',
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
    #[Group('management')]
    public function authorization_is_required_for_querying_institution_configuration_options(): void
    {
        $this->client->request(
            'GET',
            '/management/institution-configuration',
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
    public function requests_with_invalid_content_are_bad_requests(): void
    {
        $content = json_encode(['non-existing.organisation.test' => []]);
        if (!is_string($content)) {
            $this->fail('Unable to json_encode the content of the request content');
        }
        $this->client->request(
            'POST',
            '/management/institution-configuration',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'management',
                'PHP_AUTH_PW' => $this->password,
            ],
            $content,
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[DataProvider('invalidHttpMethodProvider')]
    #[Group('management')]
    public function only_post_and_get_requests_are_accepted(string $invalidHttpMethod): void
    {
        $this->client->request(
            $invalidHttpMethod,
            '/management/institution-configuration',
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
    public function a_get_request_without_content_is_valid(): void
    {
        $this->client->request(
            'GET',
            '/management/institution-configuration',
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

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('management')]
    public function json_is_returned_from_the_institution_configuration_api(): void
    {
        $this->client->request(
            'POST',
            '/management/institution-configuration',
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

    /**
     * Dataprovider for only_post_and_get_requests_are_accepted
     */
    public static function invalidHttpMethodProvider(): array
    {
        return [
            'DELETE' => ['DELETE'],
            'PUT' => ['PUT'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }
}

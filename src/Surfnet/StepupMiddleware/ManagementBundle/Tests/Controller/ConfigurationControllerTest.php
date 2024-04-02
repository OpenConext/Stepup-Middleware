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

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private KernelBrowser $client;

    private string $password;

    private string $passwordRo;

    private ORMSqliteDatabaseTool $databaseTool;

    public function setUp(): void
    {
        $tool = static::getContainer()->get(ORMSqliteDatabaseTool::class);
        if (!$tool instanceof ORMSqliteDatabaseTool) {
            $this->fail('Unable to grab the ORMSqliteDatabaseTool from the container');
        }
        $this->databaseTool = $tool;

        // Initialises schema.
        $this->databaseTool->loadFixtures([]);
        // Initialises schema.
        $this->client = static::createClient();

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

    /**
     * @test
     * @group management
     */
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

    /**
     * @test
     * @group management
     */
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

    /**
     * @test
     * @group management
     */
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

    /**
     * @test
     * @group management
     *
     * @dataProvider invalidHttpMethodProvider
     */
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

    /**
     * @test
     * @group management
     */
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

    /**
     * Dataprovider for only_post_requests_are_accepted
     */
    public function invalidHttpMethodProvider(): array
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

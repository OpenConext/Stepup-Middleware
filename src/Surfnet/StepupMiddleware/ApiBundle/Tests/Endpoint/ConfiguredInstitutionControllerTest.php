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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Endpoint;

use Generator;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfiguredInstitutionControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private KernelBrowser $client;

    /**
     * @var string[]
     */
    private array $accounts;

    /**
     * @var string
     */
    private string $endpoint;

    private ORMSqliteDatabaseTool $databaseTool;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        // Initialises schema.
        $this->databaseTool->loadFixtures([]);


        $passwordSs = $this->client->getKernel()->getContainer()->getParameter('selfservice_api_password');
        $passwordRa = $this->client->getKernel()->getContainer()->getParameter('registration_authority_api_password');
        $passwordRo = $this->client->getKernel()->getContainer()->getParameter('readonly_api_password');

        $this->accounts = ['ss' => $passwordSs, 'ra' => $passwordRa, 'apireader' => $passwordRo];

        $this->endpoint = '/institution-listing';
    }

    public function tearDown(): void
    {
        static::ensureKernelShutdown();
    }

    /**
     * @test
     * @group api
     *
     * @dataProvider invalidHttpMethodProvider
     */
    public function only_get_requests_are_accepted(string $invalidHttpMethod): void
    {
        $this->client->request(
            $invalidHttpMethod,
            $this->endpoint,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([]),
        );

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group api
     * @dataProvider notAllowedAccountsProvider
     */
    public function no_access_for_not_allowed_account(string $account): void
    {
        $this->client->request(
            'GET',
            $this->endpoint,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => $account,
                'PHP_AUTH_PW' => $this->accounts[$account],
            ],
            json_encode([]),
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group api
     */
    public function json_is_returned_from_the_api(): void
    {
        $this->client->request(
            'GET',
            $this->endpoint,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'ra',
                'PHP_AUTH_PW' => $this->accounts['ra'],
            ],
            json_encode([]),
        );

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json',
            ),
        );
    }

    /**
     * @test
     * @group api
     * @dataProvider allowedAccountsProvider
     */
    public function correct_institutions_are_returned(string $account): void
    {
        $this->client->request(
            'GET',
            $this->endpoint,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => $account,
                'PHP_AUTH_PW' => $this->accounts[$account],
            ],
            json_encode([]),
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals([], $response);
    }

    /**
     * Dataprovider for only_get_requests_are_accepted
     */
    public function invalidHttpMethodProvider(): array
    {
        return [
            'POST' => ['POST'],
            'DELETE' => ['DELETE'],
            'PUT' => ['PUT'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public function allowedAccountsProvider(): Generator
    {
        yield ['ra'];
        yield ['apireader'];
    }

    public function notAllowedAccountsProvider(): Generator
    {
        yield ['ss'];
    }
}

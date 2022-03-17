<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class DeprovisionServiceTest extends TestCase
{
    /**
     * @var DeprovisionService
     */
    private $deprovisionService;

    /**
     * @var m\LegacyMockInterface|m\MockInterface|Pipeline
     */
    private $pipeline;

    /**
     * @var m\LegacyMockInterface|m\MockInterface|ApiIdentityRepository
     */
    private $apiRepo;

    /**
     * @var m\LegacyMockInterface|m\MockInterface|IdentityRepository
     */
    private $eventRepo;

    protected function setUp(): void
    {
        $this->pipeline = m::mock(Pipeline::class);
        $this->apiRepo = m::mock(ApiIdentityRepository::class);
        $this->eventRepo = m::mock(IdentityRepository::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing(); // Not going to verify every log message at this point
        $this->deprovisionService = new DeprovisionService($this->pipeline, $this->eventRepo, $this->apiRepo, $logger);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(DeprovisionService::class, $this->deprovisionService);
    }

    /**
     * @group api-bundle
     */
    public function test_it_deals_with_non_exisiting_collab_user_id()
    {
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturnNull();
        $data = $this->deprovisionService->readUserData('urn:collab:person:example.com:maynard_keenan');

        $this->assertTrue(is_array($data));
        $this->assertEmpty($data);
    }

    /**
     * @group api-bundle
     */
    public function test_it_can_return_data()
    {
        $identity = m::mock(Identity::class);
        $identity->id = '0bf0b464-a5de-11ec-b909-0242ac120002';
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturn($identity);

        $this->eventRepo->shouldReceive('obtainInformation')->andReturn(['status' => 'OK', 'data' => []]);

        $data = $this->deprovisionService->readUserData('urn:collab:person:example.com:maynard_keenan');

        $this->assertTrue(is_array($data));
        $this->assertEquals($data['status'], 'OK');
    }
}

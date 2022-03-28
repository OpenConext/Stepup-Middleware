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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\UserNotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class DeprovisionServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(DeprovisionService::class, $this->deprovisionService);
    }

    /**
     * @group api-bundle
     */
    public function test_it_deals_with_non_exisiting_collab_user_id()
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User identified by: urn:collab:person:example.com:maynard_keenan was not found. Unable to provide deprovision data.');

        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturnNull();
        $this->deprovisionService->readUserData('urn:collab:person:example.com:maynard_keenan');
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

    public function test_deprovision_does_not_deprovision_when_user_is_not_found()
    {
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturnNull();
        $this->pipeline
            ->shouldNotHaveReceived('process');

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User identified by: urn:collab:person:example.com:maynard_keenan was not found. Unable to provide deprovision data.');
        $this->deprovisionService->deprovision('urn:collab:person:example.com:maynard_keenan');
    }
    public function test_deprovision_method_performs_the_right_to_be_forgotten_command()
    {
        $identity = m::mock(Identity::class);
        $identity->id = '0bf0b464-a5de-11ec-b909-0242ac120002';
        $identity->institution = new Institution('tool');
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturn($identity);
        $this->pipeline
            ->shouldReceive('process')
            ->withArgs(function(ForgetIdentityCommand $command){
                $this->assertEquals($command->nameId, 'urn:collab:person:example.com:maynard_keenan');
                $this->assertEquals($command->institution, 'tool');
                return true;
            })
            ->once();

        $this->deprovisionService->deprovision('urn:collab:person:example.com:maynard_keenan');
    }
}

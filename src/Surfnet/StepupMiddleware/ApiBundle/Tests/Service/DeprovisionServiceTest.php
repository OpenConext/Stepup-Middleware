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
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class DeprovisionServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DeprovisionService $deprovisionService;

    private MockInterface&Pipeline $pipeline;

    private MockInterface&ApiIdentityRepository $apiRepo;

    private MockInterface&IdentityRepository $eventRepo;

    private MockInterface&SraaRepository $sraaRepo;

    private MockInterface&RaListingRepository $raListingRepo;

    protected function setUp(): void
    {
        $this->pipeline = m::mock(Pipeline::class);
        $this->apiRepo = m::mock(ApiIdentityRepository::class);
        $this->eventRepo = m::mock(IdentityRepository::class);
        $this->sraaRepo = m::mock(SraaRepository::class);
        $this->raListingRepo = m::mock(RaListingRepository::class);

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing(); // Not going to verify every log message at this point
        $this->deprovisionService = new DeprovisionService($this->pipeline, $this->eventRepo, $this->apiRepo, $logger, $this->sraaRepo, $this->raListingRepo);
    }

    public function test_it_can_be_created(): void
    {
        $this->assertInstanceOf(DeprovisionService::class, $this->deprovisionService);
    }

    #[Group('api-bundle')]
    public function test_it_deals_with_non_exisiting_collab_user_id(): void
    {
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturnNull();
        $data = $this->deprovisionService->readUserData('urn:collab:person:example.com:maynard_keenan');
        $this->assertEmpty($data);
    }

    #[Group('api-bundle')]
    public function test_it_can_return_data(): void
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

        $this->assertEquals($data['status'], 'OK');
    }

    public function test_deprovision_does_not_deprovision_when_user_is_not_found(): void
    {
        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with('urn:collab:person:example.com:maynard_keenan')
            ->once()
            ->andReturnNull();
        $this->pipeline
            ->shouldNotHaveReceived('process');
        $this->deprovisionService->deprovision('urn:collab:person:example.com:maynard_keenan');
    }

    public function test_deprovision_method_performs_the_right_to_be_forgotten_command(): void
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
            ->withArgs(function (ForgetIdentityCommand $command): bool {
                $this->assertEquals($command->nameId, 'urn:collab:person:example.com:maynard_keenan');
                $this->assertEquals($command->institution, 'tool');
                return true;
            })
            ->once();

        $this->deprovisionService->deprovision('urn:collab:person:example.com:maynard_keenan');
    }

    public function test_is_allowed_to_deprovision_user(): void
    {
        $nameId = 'urn:collab:person:example.com:maynard_keenan';
        $identity = m::mock(Identity::class);
        $identity->id = '0bf0b464-a5de-11ec-b909-0242ac120002';
        $identity->institution = new Institution('tool');
        $identity->nameId = new NameId($nameId);

        $this->apiRepo
            ->shouldReceive('findOneByNameId')
            ->with($nameId)
            ->once()
            ->andReturn($identity);

        $this->sraaRepo
            ->shouldReceive('contains')
            ->with($identity->nameId)
            ->once()
            ->andReturn(false);

        $this->raListingRepo
            ->shouldReceive('contains')
            ->with(m::on(function (IdentityId $identityId) use ($identity): bool {
                return $identityId->getIdentityId() === $identity->id;
            }))
            ->once()
            ->andReturn(false);

        $this->deprovisionService->assertIsAllowed($nameId);
    }
}

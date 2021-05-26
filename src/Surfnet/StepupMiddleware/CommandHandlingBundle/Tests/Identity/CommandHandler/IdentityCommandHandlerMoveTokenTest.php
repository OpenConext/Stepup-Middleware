<?php

/**
 * Copyright 2021 SURFnet bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Mockery as m;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\MoveSecondFactorEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\MoveSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

/**
 * @runTestsInSeparateProcesses
 */
class IdentityCommandHandlerMoveTokenTest extends CommandHandlerTest
{
    private static $window = 3600;

    /**
     * @var AllowedSecondFactorListService|m\MockInterface
     */
    private $allowedSecondFactorListServiceMock;

    /**
     * @var m\MockInterface|IdentityProjectionRepository
     */
    private $identityProjectionRepository;

    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;


    public function setUp(): void
    {
        $this->allowedSecondFactorListServiceMock = m::mock(AllowedSecondFactorListService::class);
        $this->loaResolutionService = m::mock(LoaResolutionService::class);

        parent::setUp();
    }

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus): CommandHandler
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();
        $this->identityProjectionRepository = m::mock(IdentityProjectionRepository::class);
        $secondFactorTypeService = m::mock(SecondFactorTypeService::class);
        $secondFactorTypeService->shouldReceive('hasEqualOrHigherLoaComparedTo')->andReturn(true);
        $secondFactorProvePossessionHelper = m::mock(SecondFactorProvePossessionHelper::class);
        $configService = m::mock(InstitutionConfigurationOptionsService::class);
        $configService->shouldIgnoreMissing();

        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory
            ),
            $this->identityProjectionRepository,
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB']),
            $this->allowedSecondFactorListServiceMock,
            $secondFactorTypeService,
            $secondFactorProvePossessionHelper,
            $configService,
            $this->loaResolutionService
        );
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_second_factor_can_be_moved()
    {
        $command = new MoveSecondFactorCommand();
        $command->nameId = 'john-doe.institution2.com';
        $command->sourceInstitution = 'john-doe.institution1.com';
        $command->sourceNameId = 'john-doe.institution1.com';
        $command->targetInstitution = 'institution2.com';
        $command->email = 'john-doe@institution2.com';
        $command->oldSecondFactorId = $this->uuid();
        $command->targetIdentityId = $this->uuid();

        $registrantId = new IdentityId('OIID_SOURCE');
        $registrantInstitution = new Institution('institution1.com');
        $registrantNameId = new NameId($command->sourceNameId);
        $registrantEmail = new Email('john-doe@institution1.com');
        $registrantCommonName = new CommonName('John Doe');
        $registrantSecFacId = new SecondFactorId($command->oldSecondFactorId);
        $yubikeySecFacId = new YubikeyPublicId('00000012');

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution2.com')
            ->andReturnNull();

        $mockIdentity = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity->id = 'OIID_SOURCE';

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution1.com')
            ->andReturn($mockIdentity);

        // Phase 1: create an entity with a token for insitution 1.
        // Phase 2: move the entity to a different institution utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($command->oldSecondFactorId),
                    $yubikeySecFacId
                )
            ])
            ->when($command)
            ->then([
                new IdentityCreatedEvent(
                    new IdentityId($command->targetIdentityId),
                    new Institution($command->targetInstitution),
                    new NameId($command->nameId),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
                new MoveSecondFactorEvent(
                    new IdentityId($command->targetIdentityId),
                    new NameId($command->sourceNameId),
                    new NameId($command->nameId),
                    new Institution($command->targetInstitution),
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $yubikeySecFacId,
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
            ]);
    }

    public function test_a_second_factor_can_be_moved_to_existing_new_identity()
    {
        $command = new MoveSecondFactorCommand();
        $command->nameId = 'john-doe.institution2.com';
        $command->sourceInstitution = 'john-doe.institution1.com';
        $command->sourceNameId = 'john-doe.institution1.com';
        $command->targetInstitution = 'institution2.com';
        $command->email = 'john-doe@institution2.com';
        $command->oldSecondFactorId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $targetIdentityId = new IdentityId($command->targetIdentityId);

        $registrantId = new IdentityId('OIID_SOURCE');
        $registrantInstitution = new Institution('institution1.com');
        $registrantNameId = new NameId($command->sourceNameId);
        $registrantEmail = new Email('john-doe@institution1.com');
        $registrantCommonName = new CommonName('John Doe');
        $registrantSecFacId = new SecondFactorId($command->oldSecondFactorId);
        $yubikeySecFacId = new YubikeyPublicId('00000012');

        $mockIdentity = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity->id = 'OIID_SOURCE';
        $mockIdentity2 = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity2->id = $command->targetIdentityId;
        $mockIdentity2->commonName = (string) $registrantCommonName;

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution1.com')
            ->andReturn($mockIdentity);

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution2.com')
            ->andReturn($mockIdentity2);

        // Phase 1: create an entity with a token for insitution 1.
        // Phase 2: create the new target identity
        // Phase 3: move the entity to a different institution utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($command->oldSecondFactorId),
                    $yubikeySecFacId
                ),
            ])
            ->withAggregateId($targetIdentityId)
            ->given([
                new IdentityCreatedEvent(
                    $targetIdentityId,
                    new Institution($command->targetInstitution),
                    new NameId($command->nameId),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
            ])
            ->when($command)
            ->then([
                new MoveSecondFactorEvent(
                    $targetIdentityId,
                    new NameId($command->sourceNameId),
                    new NameId($command->nameId),
                    new Institution($command->targetInstitution),
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $yubikeySecFacId,
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
            ]);
    }
    public function test_can_not_be_moved_if_already_moved_by_hand()
    {
        $this->expectExceptionMessage("The second factor has already moved to the new identity");
        $this->expectException(\Surfnet\Stepup\Exception\DomainException::class);

        $command = new MoveSecondFactorCommand();
        $command->nameId = 'john-doe.institution2.com';
        $command->sourceInstitution = 'john-doe.institution1.com';
        $command->sourceNameId = 'john-doe.institution1.com';
        $command->targetInstitution = 'institution2.com';
        $command->email = 'john-doe@institution2.com';
        $command->oldSecondFactorId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $targetIdentityId = new IdentityId($command->targetIdentityId);

        $registrantId = new IdentityId('OIID_SOURCE');
        $registrantInstitution = new Institution('institution1.com');
        $registrantNameId = new NameId($command->sourceNameId);
        $registrantEmail = new Email('john-doe@institution1.com');
        $registrantCommonName = new CommonName('John Doe');
        $yubikeySecFacId = new YubikeyPublicId('00000012');

        $mockIdentity = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity->id = 'OIID_SOURCE';
        $mockIdentity2 = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity2->id = $command->targetIdentityId;
        $mockIdentity2->commonName = (string) $registrantCommonName;

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution1.com')
            ->andReturn($mockIdentity);

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution2.com')
            ->andReturn($mockIdentity2);

        // Phase 1: create an entity with a token for insitution 1.
        // Phase 2: create the new target identity and also register the yubikey on that identity
        // Phase 3: moving the token should not be allowed as the token is already moved by hand
        $this->scenario
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($command->oldSecondFactorId),
                    $yubikeySecFacId
                ),
            ])
            ->withAggregateId($targetIdentityId)
            ->given([
                new IdentityCreatedEvent(
                    $targetIdentityId,
                    new Institution($command->targetInstitution),
                    new NameId($command->nameId),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $targetIdentityId,
                    new NameId($command->nameId),
                    new Institution($command->targetInstitution),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB'),
                    new SecondFactorId($command->oldSecondFactorId),
                    $yubikeySecFacId
                ),
            ])
            ->when($command);
    }

    public function test_the_max_number_of_tokens_can_not_be_exeeded()
    {
        $this->expectExceptionMessage("User may not have more than 1 token(s)");
        $this->expectException(\Surfnet\Stepup\Exception\DomainException::class);

        $command = new MoveSecondFactorCommand();
        $command->nameId = 'john-doe.institution2.com';
        $command->sourceInstitution = 'john-doe.institution1.com';
        $command->sourceNameId = 'john-doe.institution1.com';
        $command->targetInstitution = 'institution2.com';
        $command->email = 'john-doe@institution2.com';
        $command->oldSecondFactorId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $targetIdentityId = new IdentityId($command->targetIdentityId);

        $registrantId = new IdentityId('OIID_SOURCE');
        $registrantInstitution = new Institution('institution1.com');
        $registrantNameId = new NameId($command->sourceNameId);
        $registrantEmail = new Email('john-doe@institution1.com');
        $registrantCommonName = new CommonName('John Doe');
        $yubikeySecFacId = new YubikeyPublicId('00000012');

        $mockIdentity = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity->id = 'OIID_SOURCE';
        $mockIdentity2 = new \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity();
        $mockIdentity2->id = $command->targetIdentityId;
        $mockIdentity2->commonName = (string) $registrantCommonName;

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution1.com')
            ->andReturn($mockIdentity);

        $this->identityProjectionRepository
            ->shouldReceive('findOneByNameId')
            ->with('john-doe.institution2.com')
            ->andReturn($mockIdentity2);

        // Phase 1: create an entity with a token for insitution 1.
        // Phase 2: create the new target identity with a fresh token (not same as to be moved token)
        // Phase 3: trying to move the yubikey token should not pass. You can only have one of the same token type
        $this->scenario
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($command->oldSecondFactorId),
                    $yubikeySecFacId
                ),
            ])
            ->withAggregateId($targetIdentityId)
            ->given([
                new IdentityCreatedEvent(
                    $targetIdentityId,
                    new Institution($command->targetInstitution),
                    new NameId($command->nameId),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $targetIdentityId,
                    new NameId($command->nameId),
                    new Institution($command->targetInstitution),
                    $registrantCommonName,
                    new Email($command->email),
                    new Locale('en_GB'),
                    new SecondFactorId('anew-very-unique-token-id'),
                    new YubikeyPublicId('00000099')
                ),
            ])
            ->when($command);
    }
}

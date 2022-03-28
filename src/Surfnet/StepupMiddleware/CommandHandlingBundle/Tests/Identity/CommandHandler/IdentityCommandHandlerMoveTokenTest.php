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
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedToEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\SecondFactorNotAllowedException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\MigrateVettedSecondFactorCommand;
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
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var m\Mock|InstitutionConfigurationOptionsService
     */
    private $configService;


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
        $this->configService = m::mock(InstitutionConfigurationOptionsService::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger
            ),
            $this->identityProjectionRepository,
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB']),
            $this->allowedSecondFactorListServiceMock,
            $secondFactorTypeService,
            $secondFactorProvePossessionHelper,
            $this->configService,
            $this->loaResolutionService
        );
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_second_factor_can_be_moved()
    {
        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = new SecondFactorId($command->targetSecondFactorId);

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2.
        // Phase 3: move the token to a different institution utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                )
            ])
            ->when($command)
            ->then([
                new SecondFactorMigratedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantSecFacId,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $sourceYubikeySecFacId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new SecondFactorMigratedToEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $targetRegistrantInstitution,
                    $sourceRegistrantSecFacId,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $sourceYubikeySecFacId
                ),
            ]);
    }

    public function test_can_not_be_moved_if_already_moved()
    {
        $this->expectExceptionMessage("The second factor was registered as a vetted second factor");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = new SecondFactorId($command->targetSecondFactorId);

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2 with the token already moved
        // Phase 3: move the token again utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new SecondFactorMigratedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantSecFacId,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $sourceYubikeySecFacId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new SecondFactorMigratedToEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $targetRegistrantInstitution,
                    $sourceRegistrantSecFacId,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $sourceYubikeySecFacId
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                ),
            ])
            ->when($command)
            ->then([]);
    }

    public function test_can_not_be_moved_if_already_present_as_bootstrapped_thus_vetted_token()
    {
        $this->expectExceptionMessage("The second factor was registered as a vetted second factor");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = $sourceRegistrantSecFacId;
        $targetYubikeySecFacId = $sourceYubikeySecFacId;

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2 with the token already moved
        // Phase 3: move the token again utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB'),
                    $targetRegistrantSecFacId,
                    $targetYubikeySecFacId
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                ),
            ])
            ->when($command)
            ->then([]);
    }


    public function test_can_not_be_moved_if_already_present_as_vetted_token()
    {
        $this->expectExceptionMessage("The second factor was registered as a vetted second factor");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = $sourceRegistrantSecFacId;
        $targetYubikeySecFacId = $sourceYubikeySecFacId;

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2 with a vetted token
        // Phase 3: move the token again utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeyPossessionProvenEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    $targetYubikeySecFacId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new EmailVerifiedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $targetYubikeySecFacId,
                    DateTime::now(),
                    'REGCODE',
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new SecondFactorVettedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('00028278'),
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NH9392'))
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                ),
            ])
            ->when($command)
            ->then([]);
    }

    public function test_can_not_be_moved_if_already_present_as_verified_token()
    {
        $this->expectExceptionMessage("The second factor was already registered as a verified second factor");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = $sourceRegistrantSecFacId;
        $targetYubikeySecFacId = $sourceYubikeySecFacId;

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2 with a verified token
        // Phase 3: move the token again utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeyPossessionProvenEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    $targetYubikeySecFacId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new EmailVerifiedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $targetYubikeySecFacId,
                    DateTime::now(),
                    'REGCODE',
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                ),
            ])
            ->when($command)
            ->then([]);
    }

    public function test_can_not_be_moved_if_already_present_as_unverified_token()
    {
        $this->expectExceptionMessage("The second factor was already registered as a unverified second factor");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = $sourceRegistrantSecFacId;
        $targetYubikeySecFacId = $sourceYubikeySecFacId;

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2 with a verified token
        // Phase 3: move the token again utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeyPossessionProvenEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantSecFacId,
                    $targetYubikeySecFacId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                ),
            ])
            ->when($command)
            ->then([]);
    }

    public function test_can_not_be_moved_to_same_institution()
    {
        $this->expectExceptionMessage("Cannot move the second factor to the same institution");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(2, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = $sourceRegistrantInstitution;
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity also with institution 1
        // Phase 3: move the token utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                )
            ])
            ->when($command)
            ->then([]);
    }

    public function test_can_not_be_moved_if_token_type_not_allowed_for_institution()
    {
        $this->expectExceptionMessage('Institution "institution2.com" does not support second factor "yubikey"');
        $this->expectException(SecondFactorNotAllowedException::class);

        $this->setUpInstitutionConfiguration(2, ['sms']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = new SecondFactorId($command->targetSecondFactorId);

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with institution 2.
        // Phase 3: move the token to a different institution utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                )
            ])
            ->when($command)
            ->then([
                new SecondFactorMigratedToEvent(
                    $targetRegistrantId,
                    $sourceRegistrantNameId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $sourceRegistrantSecFacId,
                    $targetRegistrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $sourceYubikeySecFacId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
            ]);
    }

    public function test_the_max_number_of_tokens_can_not_be_exceeded()
    {
        $this->expectExceptionMessage("User may not have more than 1 token(s)");
        $this->expectException(DomainException::class);

        $this->setUpInstitutionConfiguration(1, ['yubikey']);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = new SecondFactorId($command->targetSecondFactorId);
        $targetYubikeySecFacId = new YubikeyPublicId('00000013');

        // Phase 1: create a source entity with a token for institution 1.
        // Phase 2: create a target entity with a token for institution 2.
        // Phase 3: move the token to a different institution utilizing the move second factor command.
        $this->scenario
            ->withAggregateId($targetRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $targetRegistrantId,
                    $targetRegistrantInstitution,
                    $targetRegistrantNameId,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB'),
                    $targetRegistrantSecFacId,
                    $targetYubikeySecFacId
                )
            ])
            ->withAggregateId($sourceRegistrantId)
            ->given([
                new IdentityCreatedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantNameId,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceYubikeySecFacId
                )
            ])
            ->when($command)
            ->then([]);
    }

    private function setUpInstitutionConfiguration(int $allowedMaxNumberOfTokens, array $allowedTokenTypes)
    {
        $secondFactorTypes = [];
        foreach ($allowedTokenTypes as $type) {
            $secondFactorTypes[] = new SecondFactorType($type);
        }
        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn($allowedMaxNumberOfTokens);
        $this->allowedSecondFactorListServiceMock->shouldReceive('getAllowedSecondFactorListFor')->andReturn(AllowedSecondFactorList::ofTypes($secondFactorTypes));
        $this->configService->shouldIgnoreMissing();
    }
}

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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use DateTime as CoreDateTime;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\RecoveryTokenSecretHelper;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\MigrateVettedSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveU2fDevicePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RegistrationMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;
use function md5;

#[RunTestsInSeparateProcesses]
class IdentityCommandHandlerUniqueTypeRegistrationTest extends CommandHandlerTest
{
    private static int $window = 3600;

    private AllowedSecondFactorListService&MockInterface $allowedSecondFactorListServiceMock;

    private IdentityProjectionRepository&MockInterface $identityProjectionRepository;

    private SecondFactorTypeService&MockInterface $secondFactorTypeService;

    private SecondFactorProvePossessionHelper&MockInterface $secondFactorProvePossessionHelper;

    private InstitutionConfigurationOptionsService&MockInterface $configService;

    private LoaResolutionService&MockInterface $loaResolutionService;

    private RegistrationMailService&MockInterface $registrationMailService;

    public function setUp(): void
    {
        $this->allowedSecondFactorListServiceMock = m::mock(AllowedSecondFactorListService::class);
        $this->loaResolutionService = m::mock(LoaResolutionService::class);

        parent::setUp();
    }

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->identityProjectionRepository = m::mock(IdentityProjectionRepository::class);
        $this->secondFactorTypeService = m::mock(SecondFactorTypeService::class);
        $this->secondFactorTypeService->shouldReceive('hasEqualOrHigherLoaComparedTo')->andReturn(true);
        $this->secondFactorProvePossessionHelper = m::mock(SecondFactorProvePossessionHelper::class);
        $this->configService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->configService->shouldIgnoreMissing();
        $this->registrationMailService = m::mock(RegistrationMailService::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();
        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger,
            ),
            $this->identityProjectionRepository,
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB']),
            $this->allowedSecondFactorListServiceMock,
            $this->secondFactorTypeService,
            $this->secondFactorProvePossessionHelper,
            $this->configService,
            $this->loaResolutionService,
            m::mock(RecoveryTokenSecretHelper::class),
            $this->registrationMailService,
        );
    }

    #[Group('command-handler')]
    public function test_yubikey_type_cannot_be_proven_if_type_already_exists(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This second factor type was already registered');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId1 = new SecondFactorId(self::uuid());
        $pubId1 = new YubikeyPublicId('00028278');
        $secFacId2 = new SecondFactorId(self::uuid());
        $pubId2 = new YubikeyPublicId('00039123');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId2;
        $command->yubikeyPublicId = (string)$pubId2;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId1,
                    $pubId1,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }

    #[Group('command-handler')]
    public function test_phone_type_cannot_be_proven_if_type_already_exists(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This second factor type was already registered');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId1 = new SecondFactorId(self::uuid());
        $phoneNumber1 = new PhoneNumber('+31 (0) 612345678');
        $secFacId2 = new SecondFactorId(self::uuid());
        $phoneNumber2 = new PhoneNumber('+31 (0) 656789012');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId2;
        $command->phoneNumber = (string)$phoneNumber2;

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
                new PhonePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId1,
                    $phoneNumber1,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }


    #[Group('command-handler')]
    public function test_gssf_type_cannot_be_proven_if_type_already_exists(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This second factor type was already registered');

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $nonce = 'nonce';

        $identityId = new IdentityId(self::uuid());
        $institution = new Institution('Surfnet');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secondFactorId = new SecondFactorId(self::uuid());
        $stepupProvider = new StepupProvider('tiqr');
        $gssfId1 = new GssfId('_' . md5('Surfnet1'));
        $gssfId2 = new GssfId('_' . md5('Surfnet2'));

        $command = new ProveGssfPossessionCommand();
        $command->identityId = (string)$identityId;
        $command->secondFactorId = (string)$secondFactorId;
        $command->stepupProvider = (string)$stepupProvider;
        $command->gssfId = (string)$gssfId2;

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
                new GssfPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $stepupProvider,
                    $gssfId1,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    $nonce,
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }


    #[Test]
    #[Group('command-handler')]
    public function test_u2f_type_cannot_be_proven_if_type_already_exists(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This second factor type was already registered');

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $keyHandle1 = new U2fKeyHandle('DMUV_wX1');
        $keyHandle2 = new U2fKeyHandle('DMUV_wX2');

        $command = new ProveU2fDevicePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->keyHandle = $keyHandle2->getValue();

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
                new U2fDevicePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $keyHandle1,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }


    public function test_token_can_not_be_moved_if_type_already_exists(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This second factor type was already registered');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(3);

        $command = new MigrateVettedSecondFactorCommand();
        $command->sourceIdentityId = $this->uuid();
        $command->targetIdentityId = $this->uuid();
        $command->sourceSecondFactorId = $this->uuid();
        $command->targetSecondFactorId = $this->uuid();

        $secFacId = new SecondFactorId($this->uuid());
        $yubikeySecFacId = new YubikeyPublicId('00000011');

        $sourceRegistrantId = new IdentityId($command->sourceIdentityId);
        $sourceRegistrantInstitution = new Institution('institution1.com');
        $sourceRegistrantNameId = new NameId('john-doe.institution1.com');
        $sourceRegistrantEmail = new Email('john-doe@institution1.com');
        $sourceRegistrantCommonName = new CommonName('John Doe');
        $sourceRegistrantSecFacId = new SecondFactorId($command->sourceSecondFactorId);
        $sourceRegistrantYubikeySecFacId = new YubikeyPublicId('00000012');

        $targetRegistrantId = new IdentityId($command->targetIdentityId);
        $targetRegistrantInstitution = new Institution('institution2.com');
        $targetRegistrantNameId = new NameId('john-doe.institution2.com');
        $targetRegistrantEmail = new Email('john-doe@institution2.com');
        $targetRegistrantCommonName = new CommonName('John Doe 2');
        $targetRegistrantSecFacId = new SecondFactorId($command->targetSecondFactorId);
        $targetRegistrantYubikeySecFacId = new YubikeyPublicId('00000013');

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
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $targetRegistrantId,
                    $targetRegistrantNameId,
                    $targetRegistrantInstitution,
                    $targetRegistrantCommonName,
                    $targetRegistrantEmail,
                    new Locale('en_GB'),
                    $targetRegistrantSecFacId,
                    $targetRegistrantYubikeySecFacId,
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
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $sourceRegistrantId,
                    $sourceRegistrantNameId,
                    $sourceRegistrantInstitution,
                    $sourceRegistrantCommonName,
                    $sourceRegistrantEmail,
                    new Locale('en_GB'),
                    $sourceRegistrantSecFacId,
                    $sourceRegistrantYubikeySecFacId,
                ),
            ])
            ->when($command)
            ->then([]);
    }
}

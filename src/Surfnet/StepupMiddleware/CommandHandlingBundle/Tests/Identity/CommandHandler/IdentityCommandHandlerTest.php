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
use Mockery\Matcher\IsEqual;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRestoredEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SelfVetVettingType;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\SecondFactorNotAllowedException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\UnsupportedLocaleException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveU2fDevicePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SelfVetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendSecondFactorRegistrationEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\Exception\DuplicateIdentityException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RegistrationMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTestBase;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;
use function md5;

#[RunTestsInSeparateProcesses]
class IdentityCommandHandlerTest extends CommandHandlerTestBase
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

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_can_be_bootstrapped_with_a_yubikey_second_factor(): void
    {
        $command = new BootstrapIdentityWithYubikeySecondFactorCommand();
        $command->identityId = 'ID-ID';
        $command->nameId = 'N-ID';
        $command->institution = 'Institution';
        $command->commonName = 'Enrique';
        $command->email = 'foo@domain.invalid';
        $command->preferredLocale = 'nl_NL';
        $command->secondFactorId = 'SF-ID';
        $command->yubikeyPublicId = '93193884';

        $this->identityProjectionRepository->shouldReceive('hasIdentityWithNameIdAndInstitution')->andReturn(false);
        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $identityId = new IdentityId($command->identityId);
        $this->scenario
            ->withAggregateId($command->identityId)
            ->when($command)
            ->then([
                new IdentityCreatedEvent(
                    $identityId,
                    new Institution('Institution'),
                    new NameId('N-ID'),
                    new CommonName($command->commonName),
                    new Email($command->email),
                    new Locale('nl_NL'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    new NameId('N-ID'),
                    new Institution('Institution'),
                    new CommonName($command->commonName),
                    new Email($command->email),
                    new Locale('nl_NL'),
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('93193884'),
                ),
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_cannot_be_bootstrapped_twice(): void
    {
        $command = new BootstrapIdentityWithYubikeySecondFactorCommand();
        $command->identityId = 'ID-ID';
        $command->nameId = 'N-ID';
        $command->institution = 'Institution';
        $command->commonName = 'Enrique';
        $command->email = 'foo@domain.invalid';
        $command->preferredLocale = 'nl_NL';
        $command->secondFactorId = 'SF-ID';
        $command->yubikeyPublicId = '93193884';

        $this->identityProjectionRepository->shouldReceive('hasIdentityWithNameIdAndInstitution')->andReturn(true);
        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->expectException(DuplicateIdentityException::class);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->when($command)
            ->then([]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_yubikey_possession_can_be_proven(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:'.TokenGenerator::class)
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new YubikeyPublicId('00028278');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->yubikeyPublicId = (string)$pubId;

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

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
            ])
            ->when($command)
            ->then([
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $pubId,
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
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_yubikey_possession_cannot_be_proven_if_the_second_factor_is_not_allowed_by_the_institution(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new YubikeyPublicId('00028278');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->yubikeyPublicId = (string)$pubId;

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::ofTypes([new SecondFactorType('sms')]));

        $this->expectException(SecondFactorNotAllowedException::class);
        $this->expectExceptionMessage('does not support second factor');

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
            ])
            ->when($command);
    }

    #[Test]
    #[Group('command-handler')]
    public function yubikey_possession_cannot_be_proven_twice(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('more than 1 token(s)');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId1 = new SecondFactorId(self::uuid());
        $pubId1 = new YubikeyPublicId('00028278');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(1);

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId1;
        $command->yubikeyPublicId = (string)$pubId1;

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

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_phone_possession_can_be_proven(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:'.TokenGenerator::class)
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $phoneNumber = new PhoneNumber('+31 (0) 612345678');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->phoneNumber = (string)$phoneNumber;

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
            ])
            ->when($command)
            ->then([
                new PhonePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $phoneNumber,
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
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_phone_possession_cannot_be_proven_if_the_second_factor_is_not_allowed_by_the_institution(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $phoneNumber = new PhoneNumber('+31 (0) 612345678');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::ofTypes([new SecondFactorType('yubikey')]));

        $this->expectException(SecondFactorNotAllowedException::class);
        $this->expectExceptionMessage('does not support second factor');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->phoneNumber = (string)$phoneNumber;

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
            ])
            ->when($command)
            ->then([]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_gssf_possession_can_be_proven(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $nonce = 'nonce';
        m::mock('alias:'.TokenGenerator::class)
            ->shouldReceive('generateNonce')->once()->andReturn($nonce);

        $identityId = new IdentityId(self::uuid());
        $institution = new Institution('Surfnet');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secondFactorId = new SecondFactorId(self::uuid());
        $stepupProvider = new StepupProvider('tiqr');
        $gssfId = new GssfId('_' . md5('Surfnet'));

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(
                AllowedSecondFactorList::ofTypes(
                    [
                        new SecondFactorType('biometric'),
                        new SecondFactorType('tiqr'),
                        new SecondFactorType('anotherGssp'),
                    ],
                ),
            );

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $command = new ProveGssfPossessionCommand();
        $command->identityId = (string)$identityId;
        $command->secondFactorId = (string)$secondFactorId;
        $command->stepupProvider = (string)$stepupProvider;
        $command->gssfId = (string)$gssfId;

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
            ])
            ->when($command)
            ->then([
                new GssfPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $stepupProvider,
                    $gssfId,
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
            ]);
    }


    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_gssf_possession_can_not_be_proven_if_the_second_factor_is_not_allowed_by_the_institution(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $identityId = new IdentityId(self::uuid());
        $institution = new Institution('Surfnet');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secondFactorId = new SecondFactorId(self::uuid());
        $stepupProvider = new StepupProvider('tiqr');
        $gssfId = new GssfId('_' . md5('Surfnet'));

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::ofTypes([new SecondFactorType('sms')]));

        $this->expectException(SecondFactorNotAllowedException::class);
        $this->expectExceptionMessage('does not support second factor');

        $command = new ProveGssfPossessionCommand();
        $command->identityId = (string)$identityId;
        $command->secondFactorId = (string)$secondFactorId;
        $command->stepupProvider = (string)$stepupProvider;
        $command->gssfId = (string)$gssfId;

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
            ])
            ->when($command)
            ->then([]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_u2f_device_possession_can_be_proven(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:'.TokenGenerator::class)
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $keyHandle = new U2fKeyHandle('DMUV_wX');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(2);

        $command = new ProveU2fDevicePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->keyHandle = $keyHandle->getValue();

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
            ])
            ->when($command)
            ->then([
                new U2fDevicePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $keyHandle,
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
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_u2f_device_possession_cannot_be_proven_if_the_second_factor_is_not_allowed_by_the_institution(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId = new SecondFactorId(self::uuid());
        $keyHandle = new U2fKeyHandle('DMUV_wX');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::ofTypes([new SecondFactorType('yubikey')]));

        $this->expectException(SecondFactorNotAllowedException::class);
        $this->expectExceptionMessage('does not support second factor');

        $command = new ProveU2fDevicePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId;
        $command->keyHandle = $keyHandle->getValue();

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
            ])
            ->when($command)
            ->then([]);
    }

    #[Test]
    #[Group('command-handler')]
    public function phone_possession_cannot_be_proven_twice(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('more than 1 token(s)');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId1 = new SecondFactorId(self::uuid());
        $phoneNumber1 = new PhoneNumber('+31 (0) 612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId1;
        $command->phoneNumber = (string)$phoneNumber1;

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(1);

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

    #[Test]
    #[Group('command-handler')]
    public function cannot_prove_possession_of_arbitrary_second_factor_type_twice(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('more than 1 token(s)');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secFacId1 = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('00028278');
        $phoneNumber = new PhoneNumber('+31 (0) 676543210');

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(AllowedSecondFactorList::blank());

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(1);

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string)$id;
        $command->secondFactorId = (string)$secFacId1;
        $command->phoneNumber = (string)$phoneNumber;

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
                    $publicId,
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

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_unverified_second_factors_email_can_be_verified(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\StepupBundle\Security\OtpGenerator')
            ->shouldReceive('generate')->once()->andReturn('regcode');

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secondFactorId = new SecondFactorId(self::uuid());
        $secondFactorIdentifier = new YubikeyPublicId('00028278');

        $command = new VerifyEmailCommand();
        $command->identityId = (string)$id;
        $command->verificationNonce = 'nonce';

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
                    $secondFactorId,
                    $secondFactorIdentifier,
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
            ->when($command)
            ->then([
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_verified_second_factors_email_cannot_be_verified(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Cannot verify second factor, no unverified second factor can be verified using the given nonce',
        );

        $id = new IdentityId(self::uuid());
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');
        $secondFactorId = new SecondFactorId(self::uuid());
        $secondFactorIdentifier = new YubikeyPublicId('00028278');

        $command = new VerifyEmailCommand();
        $command->identityId = (string)$id;
        $command->verificationNonce = 'nonce';

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
                    $secondFactorId,
                    $secondFactorIdentifier,
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
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }

    #[Test]
    #[Group('command-handler')]
    public function cannot_verify_an_email_after_the_verification_window_has_closed(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot verify second factor, the verification window is closed.');

        $id = new IdentityId(self::uuid());
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('00028278');
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $preferredLocale = new Locale('en_GB');

        $command = new VerifyEmailCommand();
        $command->identityId = (string)$id;
        $command->verificationNonce = 'nonce';

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
                    $secondFactorId,
                    $publicId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        new DateTime(new CoreDateTime('-2 days')),
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale,
                ),
            ])
            ->when($command);
    }

    #[Test]
    #[Group('command-handler')]
    public function it_can_create_a_new_identity(): void
    {
        $createCommand = new CreateIdentityCommand();
        $createCommand->UUID = '1';
        $createCommand->id = '2';
        $createCommand->institution = 'A Corp.';
        $createCommand->nameId = '3';
        $createCommand->preferredLocale = 'nl_NL';
        $createCommand->email = 'a@domain.invalid';
        $createCommand->commonName = 'foobar';

        $identityId = new IdentityId($createCommand->id);
        $identityInstitution = new Institution($createCommand->institution);
        $identityNameId = new NameId($createCommand->nameId);
        $identityEmail = new Email($createCommand->email);
        $identityCommonName = new CommonName($createCommand->commonName);
        $identityPreferredLocale = new Locale($createCommand->preferredLocale);

        $createdEvent = new IdentityCreatedEvent(
            $identityId,
            $identityInstitution,
            $identityNameId,
            $identityCommonName,
            $identityEmail,
            $identityPreferredLocale,
        );

        $this->scenario
            ->given([])
            ->when($createCommand)
            ->then([
                $createdEvent,
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function an_identity_can_be_updated(): void
    {
        $id = new IdentityId('42');
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');

        $createdEvent = new IdentityCreatedEvent(
            $id,
            $institution,
            new NameId('3'),
            $commonName,
            $email,
            new Locale('de_DE'),
        );

        $updateCommand = new UpdateIdentityCommand();
        $updateCommand->id = $id->getIdentityId();
        $updateCommand->email = 'new-email@domain.invalid';
        $updateCommand->commonName = 'Henk Hendriksen';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent])
            ->when($updateCommand)
            ->then([
                new IdentityRenamedEvent($id, $institution, new CommonName($updateCommand->commonName)),
                new IdentityEmailChangedEvent($id, $institution, new Email($updateCommand->email)),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function an_identity_can_be_updated_twice_only_emitting_events_when_changed(): void
    {
        $id = new IdentityId('42');
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');

        $createdEvent = new IdentityCreatedEvent(
            $id,
            $institution,
            new NameId('3'),
            $commonName,
            $email,
            new Locale('de_DE'),
        );

        $updateCommand = new UpdateIdentityCommand();
        $updateCommand->id = $id->getIdentityId();
        $updateCommand->email = 'new-email@domain.invalid';
        $updateCommand->commonName = 'Henk Hendriksen';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent])
            ->when($updateCommand)
            ->when($updateCommand)
            ->then([
                new IdentityRenamedEvent($id, $institution, new CommonName($updateCommand->commonName)),
                new IdentityEmailChangedEvent($id, $institution, new Email($updateCommand->email)),
            ]);
    }


    #[Test]
    #[Group('command-handler')]
    public function a_deprovisioned_identity_is_restored_when_updated(): void
    {
        $id = new IdentityId('42');
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');

        $createdEvent = new IdentityCreatedEvent(
            $id,
            $institution,
            new NameId('3'),
            $commonName,
            $email,
            new Locale('de_DE'),
        );

        $forgottenEvent = new IdentityForgottenEvent(
            $id,
            $institution,
        );

        $updateCommand = new UpdateIdentityCommand();
        $updateCommand->id = $id->getIdentityId();
        $updateCommand->email = 'new-email@domain.invalid';
        $updateCommand->commonName = 'Henk Hendriksen';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent, $forgottenEvent])
            ->when($updateCommand)
            ->then([
                new IdentityRestoredEvent($id, $institution, new CommonName($updateCommand->commonName), new Email($updateCommand->email)),
            ]);
    }


    #[Test]
    #[Group('command-handler')]
    public function a_second_factor_can_be_vetted(): void
    {
        $command = new VetSecondFactorCommand();
        $command->authorityId = 'AID';
        $command->identityId = 'IID';
        $command->secondFactorId = 'ISFID';
        $command->registrationCode = 'REGCODE';
        $command->secondFactorType = 'yubikey';
        $command->secondFactorIdentifier = '00028278';
        $command->documentNumber = 'NH9392';
        $command->identityVerified = true;
        $command->provePossessionSkipped = false;

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId($this->uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantEmail = new Email('reg@domain.invalid');
        $registrantCommonName = new CommonName('Reginald Waterloo');
        $registrantSecFacId = new SecondFactorId('ISFID');
        $registrantSecFacIdentifier = new YubikeyPublicId('00028278');

        $this->secondFactorTypeService->shouldReceive('hasEqualOrLowerLoaComparedTo')->andReturn(true);

        $secondFactorType = new SecondFactorType($command->secondFactorType);
        $this->secondFactorProvePossessionHelper->shouldReceive('canSkipProvePossession')
            ->with(new IsEqual($secondFactorType))
            ->andReturn(false);

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($this->uuid()),
                    new YubikeyPublicId('00000012'),
                ),
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantSecFacIdentifier,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecFacIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('00028278'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NH9392')),
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_second_factor_cannot_be_vetted_without_a_secure_enough_vetted_second_factor(): void
    {
        $this->expectExceptionMessage("Authority does not have the required LoA");
        $this->expectException(DomainException::class);

        $command = new VetSecondFactorCommand();
        $command->authorityId = 'AID';
        $command->identityId = 'IID';
        $command->secondFactorId = 'ISFID';
        $command->registrationCode = 'REGCODE';
        $command->secondFactorType = 'yubikey';
        $command->secondFactorIdentifier = '00028278';
        $command->documentNumber = 'NH9392';
        $command->identityVerified = true;
        $command->provePossessionSkipped = false;

        $authorityId = new IdentityId($command->authorityId);
        $authorityInstitution = new Institution('Wazoo');
        $authorityNameId = new NameId($this->uuid());
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');
        $authorityPhoneSfId = new SecondFactorId($this->uuid());
        $authorityPhoneNo = new PhoneNumber('+31 (0) 612345678');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantEmail = new Email('reg@domain.invalid');
        $registrantCommonName = new CommonName('Reginald Waterloo');
        $registrantSecFacId = new SecondFactorId('ISFID');
        $registrantPubId = new YubikeyPublicId('00028278');

        $this->secondFactorTypeService->shouldReceive('hasEqualOrLowerLoaComparedTo')->andReturn(false);

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new PhonePossessionProvenEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    $authorityPhoneNo,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    DateTime::now(),
                    'regcode',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NG-RB-81')),
                ),
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantPubId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantPubId,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('00028278'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NH9392')),
                ),
            ]);
    }


    #[Test]
    #[Group('command-handler')]
    public function a_second_factor_can_be_vetted_without_a_physical_proven_possession(): void
    {
        $command = new VetSecondFactorCommand();
        $command->authorityId = 'AID';
        $command->identityId = 'IID';
        $command->secondFactorId = 'ISFID';
        $command->registrationCode = 'REGCODE';
        $command->secondFactorType = 'yubikey';
        $command->secondFactorIdentifier = '00028278';
        $command->documentNumber = 'NH9392';
        $command->identityVerified = true;
        $command->provePossessionSkipped = true;

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId($this->uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantEmail = new Email('reg@domain.invalid');
        $registrantCommonName = new CommonName('Reginald Waterloo');
        $registrantSecFacId = new SecondFactorId('ISFID');
        $registrantSecFacIdentifier = new YubikeyPublicId('00028278');

        $this->secondFactorTypeService->shouldReceive('hasEqualOrLowerLoaComparedTo')->andReturn(true);

        $secondFactorType = new SecondFactorType($command->secondFactorType);
        $this->secondFactorProvePossessionHelper->shouldReceive('canSkipProvePossession')
            ->with(new IsEqual($secondFactorType))
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($this->uuid()),
                    new YubikeyPublicId('00000012'),
                ),
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantSecFacIdentifier,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecFacIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('00028278'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NH9392')),
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_second_factor_cannot_be_vetted_without_physical_prove_of_possession_when_not_configured(): void
    {
        $this->expectExceptionMessage(
            "The possession of registrants second factor with ID 'ISFID' of type 'yubikey' has to be physically proven",
        );
        $this->expectException(DomainException::class);

        $command = new VetSecondFactorCommand();
        $command->authorityId = 'AID';
        $command->identityId = 'IID';
        $command->secondFactorId = 'ISFID';
        $command->registrationCode = 'REGCODE';
        $command->secondFactorType = 'yubikey';
        $command->secondFactorIdentifier = '00028278';
        $command->documentNumber = 'NH9392';
        $command->identityVerified = true;
        $command->provePossessionSkipped = true;

        $authorityId = new IdentityId($command->authorityId);
        $authorityInstitution = new Institution('Wazoo');
        $authorityNameId = new NameId($this->uuid());
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');
        $authorityPhoneSfId = new SecondFactorId($this->uuid());
        $authorityPhoneNo = new PhoneNumber('+31 (0) 612345678');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantEmail = new Email('reg@domain.invalid');
        $registrantCommonName = new CommonName('Reginald Waterloo');
        $registrantSecFacId = new SecondFactorId('ISFID');
        $registrantPubId = new YubikeyPublicId('00028278');

        $this->secondFactorTypeService->shouldReceive('hasEqualOrLowerLoaComparedTo')->andReturn(true);

        $secondFactorType = new SecondFactorType($command->secondFactorType);
        $this->secondFactorProvePossessionHelper->shouldReceive('canSkipProvePossession')
            ->with(new IsEqual($secondFactorType))
            ->andReturn(false);

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new PhonePossessionProvenEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    $authorityPhoneNo,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    DateTime::now(),
                    'regcode',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('NG-RB-81')),
                ),
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantPubId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantPubId,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('00028278'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('123456')),
                ),
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_can_express_its_locale_preference(): void
    {
        $command = new ExpressLocalePreferenceCommand();
        $command->identityId = $this->uuid();
        $command->preferredLocale = 'nl_NL';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_can_send_registration_mail(): void
    {
        $command = new SendSecondFactorRegistrationEmailCommand();
        $command->identityId = self::uuid();
        $command->secondFactorId = 'second-factor-id';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->registrationMailService
            ->shouldReceive('send')
            ->with($command->identityId, $command->secondFactorId);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([]); // No event is emanated from this command
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_cannot_express_a_preference_for_an_unsupported_locale(): void
    {
        $this->expectExceptionMessage("Given locale \"fi_FI\" is not a supported locale");
        $this->expectException(UnsupportedLocaleException::class);

        $command = new ExpressLocalePreferenceCommand();
        $command->identityId = $this->uuid();
        $command->preferredLocale = 'fi_FI';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB'),
                ),
            ])
            ->when($command);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function an_identity_can_express_its_locale_preference_more_than_one_time(): void
    {
        $command = new ExpressLocalePreferenceCommand();
        $command->identityId = $this->uuid();
        $command->preferredLocale = 'nl_NL';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->when($command)
            ->then([
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
            ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_second_factor_can_be_self_vetted(): void
    {
        $command = new SelfVetSecondFactorCommand();
        $command->secondFactorId = '+31 (0) 612345678';
        $command->registrationCode = 'REGCODE';
        $command->identityId = $this->uuid();
        $command->authoringSecondFactorLoa = "loa-3";
        $command->secondFactorType = 'sms';

        $authorityPhoneSfId = new SecondFactorId($this->uuid());
        $authorityPhoneNo = new PhoneNumber('+31 (0) 612345678');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('Institution');
        $registrantSecFacId = new SecondFactorId($this->uuid());
        $registrantSecPubId = new YubikeyPublicId('00028278');
        $registrantNameId = new NameId('name id');
        $registrantEmail = new Email('jack@zweiblumen.tld');
        $registrantCommonName = new CommonName('Jack Zweiblumen');

        $this->identityProjectionRepository->shouldReceive('hasIdentityWithNameIdAndInstitution')->andReturn(true);
        $loa = new Loa(1, 'identifier_loa1');
        $this->loaResolutionService->shouldReceive('getLoa')->andReturn($loa);
        $this->secondFactorTypeService->shouldReceive('getLevel')->andReturn(1);
        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                // First the existing token is vetted
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantSecPubId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecPubId,
                    DateTime::now(),
                    $command->registrationCode,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecPubId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('123456')),
                ),
                // The next token is vetted using the other token
                new PhonePossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    $authorityPhoneNo,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                // When self vetting, proof of possession is skipped, no RA verification is performed.
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SelfVetVettingType($loa),
                ),
            ]);
    }

    /**
     *
     * @todo remove this test once we drop BC support for SelfService 3.5
     */
    #[Test]
    #[RunInSeparateProcess]
    #[Group('command-handler')]
    public function a_second_factor_can_be_self_vetted_using_old_authoringSecondFactorIdentifier_command_property(): void
    {
        $command = new SelfVetSecondFactorCommand();
        $command->secondFactorId = '+31 (0) 612345678';
        $command->registrationCode = 'REGCODE';
        $command->identityId = $this->uuid();
        $command->authoringSecondFactorIdentifier = "loa-3";
        $command->secondFactorType = 'sms';

        $authorityPhoneSfId = new SecondFactorId($this->uuid());
        $authorityPhoneNo = new PhoneNumber('+31 (0) 612345678');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('Institution');
        $registrantSecFacId = new SecondFactorId($this->uuid());
        $registrantSecPubId = new YubikeyPublicId('00028278');
        $registrantNameId = new NameId('name id');
        $registrantEmail = new Email('jack@zweiblumen.tld');
        $registrantCommonName = new CommonName('Jack Zweiblumen');

        $this->identityProjectionRepository->shouldReceive('hasIdentityWithNameIdAndInstitution')->andReturn(true);
        $loa = new Loa(1, 'identifier_loa1');
        $this->loaResolutionService->shouldReceive('getLoa')->andReturn($loa);
        $this->secondFactorTypeService->shouldReceive('getLevel')->andReturn(1);
        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                // First the existing token is vetted
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantSecPubId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecPubId,
                    DateTime::now(),
                    $command->registrationCode,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecPubId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('123456')),
                ),
                // The next token is vetted using the other token
                new PhonePossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    $authorityPhoneNo,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                // When self vetting, proof of possession is skipped, no RA verification is performed.
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new SelfVetVettingType($loa),
                ),
            ]);
    }
}

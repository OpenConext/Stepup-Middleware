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
use Mockery as m;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Helper\RecoveryTokenSecretHelper;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorsAllRevokedEvent;
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
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RegistrationMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

#[RunTestsInSeparateProcesses]
class SecondFactorRevocationTest extends CommandHandlerTest
{
    private static int $window = 3600;

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();
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
            m::mock(IdentityProjectionRepository::class),
            ConfigurableSettings::create(self::$window, []),
            m::mock(AllowedSecondFactorListService::class),
            m::mock(SecondFactorTypeService::class)->shouldIgnoreMissing(),
            m::mock(SecondFactorProvePossessionHelper::class)->shouldIgnoreMissing(),
            m::mock(InstitutionConfigurationOptionsService::class)->shouldIgnoreMissing(),
            m::mock(LoaResolutionService::class),
            m::mock(RecoveryTokenSecretHelper::class),
            m::mock(RegistrationMailService::class),
        );
    }

    #[Test]
    #[Group('command-handler')]
    public function an_identity_can_revoke_its_own_unverified_second_factor(): void
    {
        $command = new RevokeOwnSecondFactorCommand();
        $command->identityId = '42';
        $command->secondFactorId = self::uuid();

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId($command->secondFactorId);
        $secondFactorIdentifier = new YubikeyPublicId('00890782');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('3'),
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
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
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new UnverifiedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function an_identity_can_revoke_its_own_verified_second_factor(): void
    {
        $command = new RevokeOwnSecondFactorCommand();
        $command->identityId = '42';
        $command->secondFactorId = self::uuid();

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId($command->secondFactorId);
        $secondFactorIdentifier = new YubikeyPublicId('00890782');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('3'),
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
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
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'SOMEREGISTRATIONCODE',
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new VerifiedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function an_identity_can_revoke_its_own_vetted_second_factor(): void
    {
        $command = new RevokeOwnSecondFactorCommand();
        $command->identityId = '42';
        $command->secondFactorId = self::uuid();

        $identityId = new IdentityId($command->identityId);
        $nameId = new NameId('3');
        $institution = new Institution('A Corp.');
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId($command->secondFactorId);
        $secondFactorType = new SecondFactorType('yubikey');
        $secondFactorIdentifier = new YubikeyPublicId('00890782');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
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
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType,
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'SOMEREGISTRATIONCODE',
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType,
                    $secondFactorIdentifier,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('DOCUMENT_42')),
                ),
            ])
            ->when($command)
            ->then([
                new VettedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType,
                    $secondFactorIdentifier,
                ),
                new VettedSecondFactorsAllRevokedEvent(
                    $identityId,
                    $institution,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_registration_authority_can_revoke_an_unverified_second_factor(): void
    {
        $command = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId = static::uuid();
        $command->identityId = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId(static::uuid());
        $authorityInstitution = new Institution('SURFnet');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('SURFnet');
        $registrantNameId = new NameId('3');
        $registrantSecondFactorId = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorIdentifier = new YubikeyPublicId('00890782');
        $registrantEmail = new Email('matti@domain.invalid');
        $registrantCommonName = new CommonName('Matti Vanhanen');

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
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('12345678'),
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorIdentifier,
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
            ])
            ->when($command)
            ->then([
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    new SecondFactorType('yubikey'),
                    $registrantSecondFactorIdentifier,
                    $authorityId,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_registration_authority_can_revoke_a_verified_second_factor(): void
    {
        $command = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId = static::uuid();
        $command->identityId = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId(static::uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantSecondFactorId = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType = new SecondFactorType('yubikey');
        $registrantSecondFactorIdentifier = new YubikeyPublicId('00890782');
        $registrantEmail = new Email('matti@domain.invalid');
        $registrantCommonName = new CommonName('Matti Vanhanen');

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
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('12345678'),
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorIdentifier,
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    DateTime::now(),
                    'REGISTRATION_CODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    $authorityId,
                ),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    public function a_registration_authority_can_revoke_a_vetted_second_factor(): void
    {
        $command = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId = static::uuid();
        $command->identityId = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId(static::uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantSecondFactorId = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType = new SecondFactorType('yubikey');
        $registrantSecondFactorIdentifier = new YubikeyPublicId('00890782');
        $registrantEmail = new Email('matti@domain.invalid');
        $registrantCommonName = new CommonName('Matti Vanhanen');

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
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('12345678'),
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorIdentifier,
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    DateTime::now(),
                    'REGISTRATION_CODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('DOCUMENT_NUMBER')),
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithVettedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    new SecondFactorType('yubikey'),
                    $registrantSecondFactorIdentifier,
                    $authorityId,
                ),
                new VettedSecondFactorsAllRevokedEvent(
                    $registrantId,
                    $registrantInstitution,
                ),
            ]);
    }


    #[Test]
    #[Group('command-handler')]
    public function a_registration_authority_can_revoke_a_possession_proved_skipped_vetted_second_factor(): void
    {
        $command = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId = static::uuid();
        $command->identityId = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId(static::uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantSecondFactorId = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType = new SecondFactorType('yubikey');
        $registrantSecondFactorIdentifier = new YubikeyPublicId('00890782');
        $registrantEmail = new Email('matti@domain.invalid');
        $registrantCommonName = new CommonName('Matti Vanhanen');

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
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('12345678'),
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorIdentifier,
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    DateTime::now(),
                    'REGISTRATION_CODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('DOCUMENT_NUMBER')),
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithVettedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    new SecondFactorType('yubikey'),
                    $registrantSecondFactorIdentifier,
                    $authorityId,
                ),
                new VettedSecondFactorsAllRevokedEvent(
                    $registrantId,
                    $registrantInstitution,
                ),
            ]);
    }

    /**
     * Test if the VettedSecondFactorsAllRevokedEvent is not triggered with multiple 2fa's
     */
    #[Test]
    #[Group('command-handler')]
    public function a_registration_authority_can_revoke_one_of_multiple_vetted_second_factors(): void
    {
        $command = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId = static::uuid();
        $command->identityId = static::uuid();
        $command->secondFactorId = static::uuid();
        $secondFactorId2 = static::uuid();

        $authorityId = new IdentityId($command->authorityId);
        $authorityNameId = new NameId(static::uuid());
        $authorityInstitution = new Institution('Wazoo');
        $authorityEmail = new Email('info@domain.invalid');
        $authorityCommonName = new CommonName('Henk Westbroek');

        $registrantId = new IdentityId($command->identityId);
        $registrantInstitution = new Institution('A Corp.');
        $registrantNameId = new NameId('3');
        $registrantSecondFactorId = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType = new SecondFactorType('yubikey');
        $registrantSecondFactorIdentifier = new YubikeyPublicId('00890782');
        $registrantEmail = new Email('matti@domain.invalid');
        $registrantCommonName = new CommonName('Matti Vanhanen');

        $registrantSecondFactorId2 = new SecondFactorId($secondFactorId2);
        $registrantSecondFactorType2 = new SecondFactorType('u2f');
        $registrantSecondFactorIdentifier2 = new U2fKeyHandle('00890783');

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
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('12345678'),
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
                // First second factor
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorIdentifier,
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
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    DateTime::now(),
                    'REGISTRATION_CODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantSecondFactorIdentifier,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('DOCUMENT_NUMBER')),
                ),
                // Second second factor
                new U2fDevicePossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId2,
                    $registrantSecondFactorIdentifier2,
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
                    $registrantSecondFactorId2,
                    $registrantSecondFactorType2,
                    $registrantSecondFactorIdentifier2,
                    DateTime::now(),
                    'REGISTRATION_CODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecondFactorId2,
                    $registrantSecondFactorType2,
                    $registrantSecondFactorIdentifier2,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB'),
                    new OnPremiseVettingType(new DocumentNumber('DOCUMENT_NUMBER')),
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithVettedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    new SecondFactorType('yubikey'),
                    $registrantSecondFactorIdentifier,
                    $authorityId,
                ),
            ]);
    }
}

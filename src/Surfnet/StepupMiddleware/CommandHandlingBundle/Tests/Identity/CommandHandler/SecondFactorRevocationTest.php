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

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

/**
 * @runTestsInSeparateProcesses
 */
class SecondFactorRevocationTest extends CommandHandlerTest
{
    private static $window = 3600;

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new IdentityCommandHandler(
            new IdentityRepository($eventStore, $eventBus, $aggregateFactory),
            ConfigurableSettings::create(self::$window)
        );
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_revoke_its_own_unverified_second_factor()
    {
        $command                 = new RevokeOwnSecondFactorCommand();
        $command->identityId     = '42';
        $command->secondFactorId = self::uuid();

        $identityId        = new IdentityId($command->identityId);
        $institution       = new Institution('A Corp.');
        $identifyingDataId = IdentifyingDataId::fromIdentityId($identityId);
        $secFacId          = new SecondFactorId($command->secondFactorId);
        $pubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('3'),
                    $identifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secFacId,
                    $pubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $identifyingDataId,
                    'nonce',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new UnverifiedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secFacId,
                    new SecondFactorType('yubikey')
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_revoke_its_own_verified_second_factor()
    {
        $command                 = new RevokeOwnSecondFactorCommand();
        $command->identityId     = '42';
        $command->secondFactorId = self::uuid();

        $identityId        = new IdentityId($command->identityId);
        $institution       = new Institution('A Corp.');
        $identifyingDataId = IdentifyingDataId::fromIdentityId($identityId);
        $secFacId          = new SecondFactorId($command->secondFactorId);
        $pubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('3'),
                    $identifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secFacId,
                    $pubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $identifyingDataId,
                    'nonce',
                    'en_GB'
                ),
                new EmailVerifiedEvent(
                    $identityId,
                    $institution,
                    $secFacId,
                    new SecondFactorType('yubikey'),
                    DateTime::now(),
                    $identifyingDataId,
                    'SOMEREGISTRATIONCODE',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new VerifiedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secFacId,
                    new SecondFactorType('yubikey')
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_revoke_its_own_vetted_second_factor()
    {
        $command                 = new RevokeOwnSecondFactorCommand();
        $command->identityId     = '42';
        $command->secondFactorId = self::uuid();

        $identityId        = new IdentityId($command->identityId);
        $nameId            = new NameId('3');
        $institution       = new Institution('A Corp.');
        $identifyingDataId = IdentifyingDataId::fromIdentityId($identityId);
        $secondFactorId    = new SecondFactorId($command->secondFactorId);
        $secondFactorType  = new SecondFactorType('yubikey');
        $yubikeyId         = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $identifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $yubikeyId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $identifyingDataId,
                    'nonce',
                    'en_GB'
                ),
                new EmailVerifiedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType,
                    DateTime::now(),
                    $identifyingDataId,
                    'SOMEREGISTRATIONCODE',
                    'en_GB'
                ),
                new SecondFactorVettedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType,
                    $identifyingDataId,
                    (string) $yubikeyId,
                    'DOCUMENT_42',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new VettedSecondFactorRevokedEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $secondFactorType
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_registration_authority_can_revoke_an_unverified_second_factor()
    {
        $command                 = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId    = static::uuid();
        $command->identityId     = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId                = new IdentityId($command->authorityId);
        $authorityNameId            = new NameId(static::uuid());
        $authorityInstitution       = new Institution('SURFnet');
        $authorityIdentifyingDataId = IdentifyingDataId::fromIdentityId($authorityId);

        $registrantId                = new IdentityId($command->identityId);
        $registrantInstitution       = new Institution('SURFnet');
        $registrantNameId            = new NameId('3');
        $registrantIdentifyingDataId = IdentifyingDataId::fromIdentityId($registrantId);
        $registrantSecFacId          = new SecondFactorId($command->secondFactorId);
        $registrantPubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityIdentifyingDataId
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityIdentifyingDataId,
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('ccccvkdowiej')
                )
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantIdentifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantPubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $registrantIdentifyingDataId,
                    'nonce',
                    'en_GB'
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $authorityId
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_registration_authority_can_revoke_a_verified_second_factor()
    {
        $command                 = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId    = static::uuid();
        $command->identityId     = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId                = new IdentityId($command->authorityId);
        $authorityNameId            = new NameId(static::uuid());
        $authorityInstitution       = new Institution('Wazoo');
        $authorityIdentifyingDataId = IdentifyingDataId::fromIdentityId($authorityId);

        $registrantId                = new IdentityId($command->identityId);
        $registrantInstitution       = new Institution('A Corp.');
        $registrantNameId            = new NameId('3');
        $registrantIdentifyingDataId = IdentifyingDataId::fromIdentityId($registrantId);
        $registrantSecondFactorId    = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType  = new SecondFactorType('yubikey');
        $registrantPubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityIdentifyingDataId
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityIdentifyingDataId,
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('ccccvkdowiej')
                )
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantIdentifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantPubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $registrantIdentifyingDataId,
                    'nonce',
                    'en_GB'
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    DateTime::now(),
                    $registrantIdentifyingDataId,
                    'REGISTRATION_CODE',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $authorityId
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_registration_authority_can_revoke_a_vetted_second_factor()
    {
        $command                 = new RevokeRegistrantsSecondFactorCommand();
        $command->authorityId    = static::uuid();
        $command->identityId     = static::uuid();
        $command->secondFactorId = static::uuid();

        $authorityId                = new IdentityId($command->authorityId);
        $authorityNameId            = new NameId(static::uuid());
        $authorityInstitution       = new Institution('Wazoo');
        $authorityIdentifyingDataId = IdentifyingDataId::fromIdentityId($authorityId);

        $registrantId                = new IdentityId($command->identityId);
        $registrantInstitution       = new Institution('A Corp.');
        $registrantNameId            = new NameId('3');
        $registrantIdentifyingDataId = IdentifyingDataId::fromIdentityId($registrantId);
        $registrantSecondFactorId    = new SecondFactorId($command->secondFactorId);
        $registrantSecondFactorType  = new SecondFactorType('yubikey');
        $registrantPubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityIdentifyingDataId
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityIdentifyingDataId,
                    new SecondFactorId(static::uuid()),
                    new YubikeyPublicId('ccccvkdowiej')
                )
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantIdentifyingDataId
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantPubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $registrantIdentifyingDataId,
                    'nonce',
                    'en_GB'
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    DateTime::now(),
                    $registrantIdentifyingDataId,
                    'REGISTRATION_CODE',
                    'en_GB'
                ),
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    $registrantSecondFactorType,
                    $registrantIdentifyingDataId,
                    $registrantPubId,
                    'DOCUMENT_NUMBER',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new CompliedWithVettedSecondFactorRevocationEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecondFactorId,
                    new SecondFactorType('yubikey'),
                    $authorityId
                ),
            ]);
    }
}

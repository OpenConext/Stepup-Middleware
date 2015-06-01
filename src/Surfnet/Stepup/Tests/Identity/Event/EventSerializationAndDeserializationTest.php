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

namespace Surfnet\Stepup\Tests\Identity\Event;

use Broadway\Serializer\SerializableInterface;
use PHPUnit_Framework_TestCase as UnitTest;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;

class EventSerializationAndDeserializationTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider eventProvider
     * @param SerializableInterface $event
     */
    public function an_event_should_be_the_same_after_serialization_and_deserialization(SerializableInterface $event)
    {
        $class = get_class($event);
        $this->assertTrue($event == call_user_func([$class, 'deserialize'], $event->serialize()));
    }

    /**
     * @test
     * @group domain
     */
    public function an_email_verification_window_should_be_the_same_after_serialization_and_deserialization()
    {
        $window = EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now());

        $this->assertTrue($window == EmailVerificationWindow::deserialize($window->serialize()));
    }

    public function eventProvider()
    {
        return [
            'CompliedWithUnverifiedSecondFactorRevocationEvent' => [
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId',
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVerifiedSecondFactorRevocationEvent' => [
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId',
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVettedSecondFactorRevocationEvent' => [
                new CompliedWithVettedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId',
                    new IdentityId(static::UUID())
                )
            ],
            'EmailVerifiedEvent' => [
                new EmailVerifiedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId',
                    DateTime::now(),
                    new IdentifyingDataId(static::UUID()),
                    '123',
                    new Locale('en_GB')
                )
            ],
            'IdentityCreatedEvent' => [
                new IdentityCreatedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('BabelFish Inc'),
                    new NameId('42'),
                    new Locale('en_GB'),
                    new IdentifyingDataId(static::UUID())
                )
            ],
            'IdentityEmailChangedEvent' => [
                new IdentityEmailChangedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new IdentifyingDataId(static::UUID())
                )
            ],
            'IdentityRenamedEvent' => [
                new IdentityRenamedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new IdentifyingDataId(static::UUID())
                )
            ],
            'PhonePossessionProvenEvent' => [
                new PhonePossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new PhoneNumber('+31 (0) 612345678'),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now()),
                    new IdentifyingDataId(static::UUID()),
                    '42',
                    new Locale('en_GB')
                )
            ],
            'UnverifiedSecondFactorRevokedEvent' => [
                new UnverifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId'
                )
            ],
            'VerifiedSecondFactorRevokedEvent' => [
                new VerifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId'
                )
            ],
            'VettedSecondFactorRevokedEvent' => [
                new VettedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    'secondFactorId'
                )
            ],
            'YubikeyPossessionProvenEvent' => [
                new YubikeyPossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new YubikeyPublicId('this_is_mah_yubikey'),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now()),
                    new IdentifyingDataId(static::UUID()),
                    '42',
                    new Locale('en_GB')
                )
            ],
            'GssfPossessionProvenEvent' => [
                new GssfPossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new StepupProvider('tiqr'),
                    new GssfId('_' . md5('Tiqr')),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now()),
                    new IdentifyingDataId(static::UUID()),
                    '42',
                    new Locale('en_GB')
                )
            ],
            'IdentityAccreditedAsRaEvent' => [
                new IdentityAccreditedAsRaEvent(
                    new IdentityId(static::UUID()),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish Inc.'),
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('somewhere behind you'),
                    new ContactInformation('Call me maybe')
                )
            ],
            'IdentityAccreditedAsRaaEvent' => [
                new IdentityAccreditedAsRaaEvent(
                    new IdentityId(static::UUID()),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish Inc.'),
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                    new Location('somewhere behind you'),
                    new ContactInformation('Call me maybe')
                )
            ],
            'RegistrationAuthorityInformationAmendedEvent' => [
                new RegistrationAuthorityInformationAmendedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Blue Note'),
                    new NameId(md5('Coleman Hawkins')),
                    new Location('New York'),
                    new ContactInformation("131 West 3rd Street, NY")
                )
            ],
        ];
    }

    private static function UUID()
    {
        return (string) Uuid::uuid4();
    }
}

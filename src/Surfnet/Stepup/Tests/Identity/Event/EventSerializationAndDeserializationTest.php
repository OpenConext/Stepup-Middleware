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

use PHPUnit_Framework_TestCase as UnitTest;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

class EventSerializationAndDeserializationTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider eventProvider
     */
    public function an_event_should_be_the_same_after_serialization_and_deserialization($event)
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
                    new SecondFactorId(static::UUID()),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVerifiedSecondFactorRevocationEvent' => [
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID()),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVettedSecondFactorRevocationEvent' => [
                new CompliedWithVettedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID()),
                    new IdentityId(static::UUID())
                )
            ],
            'EmailVerifiedEvent' => [
                new EmailVerifiedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc'),
                    new SecondFactorId(static::UUID()),
                    DateTime::now(),
                    '123',
                    'Arthur Dent',
                    'arthur@babelfish.inc',
                    'en_GB'
                )
            ],
            'IdentityCreatedEvent' => [
                new IdentityCreatedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('BabelFish Inc'),
                    new NameId('42'),
                    'arthur@babelfish.inc',
                    'Arthur Dent'
                )
            ],
            'IdentityEmailChangedEvent' => [
                new IdentityEmailChangedEvent(
                    new IdentityId(static::UUID()),
                    'arthur@babelfish.inc',
                    'Arthur.Dent@babelfish.inc'
                )
            ],
            'IdentityRenamedEvent' => [
                new IdentityRenamedEvent(
                    new IdentityId(static::UUID()),
                    'Arthur Dent',
                    'A. Dent'
                )
            ],
            'PhonePossessionProvenEvent' => [
                new PhonePossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID()),
                    new PhoneNumber('0612345678'),
                    DateTime::now(),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now()),
                    '42',
                    'Arthur Dent',
                    'arthur@babelfish.inc',
                    'en_GB'
                )
            ],
            'UnverifiedSecondFactorRevokedEvent' => [
                new UnverifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID())
                )
            ],
            'VerifiedSecondFactorRevokedEvent' => [
                new VerifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID())
                )
            ],
            'VettedSecondFactorRevokedEvent' => [
                new VettedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID())
                )
            ],
            'YubikeyPossessionProvenEvent' => [
                new YubikeyPossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new SecondFactorId(static::UUID()),
                    new YubikeyPublicId('this_is_mah_yubikey'),
                    DateTime::now(),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now()),
                    '42',
                    'Arthur Dent',
                    'arthur@babelfish.inc',
                    'en_GB'
                )
            ]
        ];
    }

    private static function UUID()
    {
        return (string) Uuid::uuid4();
    }
}

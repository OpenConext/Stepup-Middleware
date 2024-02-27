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

use Broadway\Serializer\Serializable as SerializableInterface;
use DateTime as CoreDateTime;
use PHPUnit\Framework\TestCase as UnitTest;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class EventSerializationAndDeserializationTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider eventProvider
     * @param SerializableInterface $event
     */
    public function an_event_should_be_the_same_after_serialization_and_deserialization(SerializableInterface $event): void
    {
        $isForgettableEvent = $event instanceof Forgettable;
        $providesSensitiveData = method_exists($event, 'getSensitiveData') || method_exists($event, 'setSensitiveData');

        if (!$isForgettableEvent && $providesSensitiveData) {
            $this->fail(sprintf(
                'You provide sensitive data in %s, but do not implement %s',
                get_class($event),
                'Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable'
            ));
        }

        $serializedEvent = $event->serialize();
        if ($isForgettableEvent) {
            $sensitiveData = $event->getSensitiveData();
        }

        $deserializedEvent = $event::deserialize($serializedEvent);
        if ($isForgettableEvent) {
            $deserializedEvent->setSensitiveData($sensitiveData);
        }

        $this->assertTrue($event == $deserializedEvent);
    }

    /**
     * @test
     * @group domain
     * @dataProvider serializedDataProvider
     * @param string $serializedData
     * @param string $serializedSensitiveData
     * @param SerializableInterface $event
     */
    public function an_serialized_event_should_be_the_same(string $serializedData, string $serializedSensitiveData, SerializableInterface $event): void
    {
        $isForgettableEvent = $event instanceof Forgettable;

        $serializedDataArray = json_decode($serializedData, true);
        $serializedSensitiveDataArray = json_decode($serializedSensitiveData, true);

        $deserializedEvent = $event::deserialize($serializedDataArray);
        if ($isForgettableEvent) {
            $deserializedEvent->setSensitiveData(SensitiveData::deserialize($serializedSensitiveDataArray));
        }

        $this->assertEquals($event, $deserializedEvent);
    }

    /**
     * @test
     * @group domain
     */
    public function an_email_verification_window_should_be_the_same_after_serialization_and_deserialization(): void
    {
        // use a fixed datetime instance, to prevent microsecond precision issues in PHP 7.1+
        $startDateTime = new DateTime(new CoreDateTime('@1000'));
        $window = EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), $startDateTime);

        $this->assertTrue($window == EmailVerificationWindow::deserialize($window->serialize()));
    }

    public function eventProvider(): array
    {
        return [
            'CompliedWithUnverifiedSecondFactorRevocationEvent:sms' => [
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+358 (0) 687654321'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithUnverifiedSecondFactorRevocationEvent:yubikey' => [
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithUnverifiedSecondFactorRevocationEvent:tiqr' => [
                new CompliedWithUnverifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVerifiedSecondFactorRevocationEvent:sms' => [
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+0 (0) 000000000'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVerifiedSecondFactorRevocationEvent:yubikey' => [
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVerifiedSecondFactorRevocationEvent:tiqr' => [
                new CompliedWithVerifiedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVettedSecondFactorRevocationEvent:sms' => [
                new CompliedWithVettedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+0 (0) 000000000'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVettedSecondFactorRevocationEvent:yubikey' => [
                new CompliedWithVettedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382'),
                    new IdentityId(static::UUID())
                )
            ],
            'CompliedWithVettedSecondFactorRevocationEvent:tiqr' => [
                new CompliedWithVettedSecondFactorRevocationEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp'),
                    new IdentityId(static::UUID())
                )
            ],
            'EmailVerifiedEvent:sms' => [
                new EmailVerifiedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+0 (0) 000000000'),
                    new DateTime(new CoreDateTime('@1000')),
                    '123',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Locale('en_GB')
                )
            ],
            'EmailVerifiedEvent:yubikey' => [
                new EmailVerifiedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382'),
                    new DateTime(new CoreDateTime('@1000')),
                    '123',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Locale('en_GB')
                )
            ],
            'EmailVerifiedEvent:tiqr' => [
                new EmailVerifiedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp'),
                    new DateTime(new CoreDateTime('@1000')),
                    '123',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Locale('en_GB')
                )
            ],
            'IdentityCreatedEvent' => [
                new IdentityCreatedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('BabelFish Inc'),
                    new NameId('42'),
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Locale('en_GB')
                )
            ],
            'IdentityEmailChangedEvent' => [
                new IdentityEmailChangedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new Email('info@example.invalid')
                )
            ],
            'IdentityRenamedEvent' => [
                new IdentityRenamedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new CommonName('Henk Westbroek')
                )
            ],
            'PhonePossessionProvenEvent' => [
                new PhonePossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new PhoneNumber('+31 (0) 612345678'),
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), new DateTime(new CoreDateTime('@1000'))),
                    '42',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Locale('en_GB')
                )
            ],
            'UnverifiedSecondFactorRevokedEvent:sms' => [
                new UnverifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+31 (0) 612345678')
                )
            ],
            'UnverifiedSecondFactorRevokedEvent:yubikey' => [
                new UnverifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382')
                )
            ],
            'UnverifiedSecondFactorRevokedEvent:tiqr' => [
                new UnverifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp')
                )
            ],
            'VerifiedSecondFactorRevokedEvent:sms' => [
                new VerifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    PhoneNumber::unknown()
                )
            ],
            'VerifiedSecondFactorRevokedEvent:yubikey' => [
                new VerifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382')
                )
            ],
            'VerifiedSecondFactorRevokedEvent:tiqr' => [
                new VerifiedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp')
                )
            ],
            'VettedSecondFactorRevokedEvent:sms' => [
                new VettedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('sms'),
                    new PhoneNumber('+1 (0) 5155550100')
                )
            ],
            'VettedSecondFactorRevokedEvent:yubikey' => [
                new VettedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('01906382')
                )
            ],
            'VettedSecondFactorRevokedEvent:tiqr' => [
                new VettedSecondFactorRevokedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new SecondFactorType('tiqr'),
                    new GssfId('bleep-blorp')
                )
            ],
            'YubikeyPossessionProvenEvent' => [
                new YubikeyPossessionProvenEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new SecondFactorId(static::UUID()),
                    new YubikeyPublicId('19382933'),
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), new DateTime(new CoreDateTime('@1000'))),
                    '42',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
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
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), new DateTime(new CoreDateTime('@1000'))),
                    '42',
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
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
            'AppointedAsRaaEvent' => [
                new AppointedAsRaaEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId'))
                )
            ],
            'AppointedAsRaEvent' => [
                new AppointedAsRaEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId'))
                )
            ],
            'RegistrationAuthorityRetractedEvent' => [
                new RegistrationAuthorityRetractedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId')),
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid')
                )
            ],
            'LocalePreferenceExpressedEvent' => [
                new LocalePreferenceExpressedEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new Locale('fi_FI')
                )
            ],
            'AppointedAsRaaForInstitutionEvent' => [
                new AppointedAsRaaForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish BV.')
                )
            ],
            'AppointedAsRaForInstitutionEvent' => [
                new AppointedAsRaForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish BV.')
                )
            ],
            'IdentityAccreditedAsRaForInstitutionEvent' => [
                new IdentityAccreditedAsRaForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish Inc.'),
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('somewhere behind you'),
                    new ContactInformation('Call me maybe'),
                    new Institution('Babelfish BV.')
                )
            ],
            'IdentityAccreditedAsRaaForInstitutionEvent' => [
                new IdentityAccreditedAsRaaForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new NameId(md5('someNameId')),
                    new Institution('Babelfish Inc.'),
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                    new Location('somewhere behind you'),
                    new ContactInformation('Call me maybe'),
                    new Institution('Babelfish BV.')
                )
            ],
            'RegistrationAuthorityInformationAmendedForInstitutionEvent' => [
                new RegistrationAuthorityInformationAmendedForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Blue Note'),
                    new NameId(md5('Coleman Hawkins')),
                    new Location('New York'),
                    new ContactInformation("131 West 3rd Street, NY"),
                    new Institution('Babelfish Inc.')
                )
            ],
            'RegistrationAuthorityRetractedForInstitutionEvent' => [
                new RegistrationAuthorityRetractedForInstitutionEvent(
                    new IdentityId(static::UUID()),
                    new Institution('Babelfish Inc.'),
                    new NameId(md5('someNameId')),
                    new CommonName('Henk Westbroek'),
                    new Email('info@example.invalid'),
                    new Institution('Babelfish Inc.')
                )
            ],
        ];
    }

    public function serializedDataProvider(): array{
        return [
            // Tests for changes in BC support for adding the VettingType in the SecondFactorVettedEvents in favour of the 'DocumentNumber'
            'SecondFactorVettedEvent:support-new-event-with-vetting-type' => [
                '{"identity_id":"b260f10b-ce7c-4d09-b6a4-50a3923d637f","name_id":"urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1","identity_institution":"institution-d.example.com","second_factor_id":"512de1ff-0ae0-41b7-bb21-b71d77e570b8","second_factor_type":"yubikey","preferred_locale":"nl_NL"}',
                '{"common_name":"jane-d1 Institution-D.EXAMPLE.COM","email":"jane+jane-d1@stepup.example.com","second_factor_type":"yubikey","second_factor_identifier":"123465293846985","vetting_type":{"type":"on-premise","document_number":"012345678"}}',
                new SecondFactorVettedEvent(
                    new IdentityId('b260f10b-ce7c-4d09-b6a4-50a3923d637f'),
                    new NameId('urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1'),
                    new Institution('institution-d.example.com'),
                    new SecondFactorId('512de1ff-0ae0-41b7-bb21-b71d77e570b8'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('123465293846985'),
                    new CommonName('jane-d1 Institution-D.EXAMPLE.COM'),
                    new Email('jane+jane-d1@stepup.example.com'),
                    new Locale('nl_NL'),
                    new OnPremiseVettingType(new DocumentNumber('012345678'))
                ),
            ],
            'SecondFactorVettedEvent:support-old-event-with-document-number' => [
                '{"identity_id":"b260f10b-ce7c-4d09-b6a4-50a3923d637f","name_id":"urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1","identity_institution":"institution-d.example.com","second_factor_id":"512de1ff-0ae0-41b7-bb21-b71d77e570b8","second_factor_type":"yubikey","preferred_locale":"nl_NL"}',
                '{"common_name":"jane-d1 Institution-D.EXAMPLE.COM","email":"jane+jane-d1@stepup.example.com","second_factor_type":"yubikey","second_factor_identifier":"123465293846985","document_number":"012345678"}',
                new SecondFactorVettedEvent(
                    new IdentityId('b260f10b-ce7c-4d09-b6a4-50a3923d637f'),
                    new NameId('urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1'),
                    new Institution('institution-d.example.com'),
                    new SecondFactorId('512de1ff-0ae0-41b7-bb21-b71d77e570b8'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('123465293846985'),
                    new CommonName('jane-d1 Institution-D.EXAMPLE.COM'),
                    new Email('jane+jane-d1@stepup.example.com'),
                    new Locale('nl_NL'),
                    new OnPremiseVettingType(new DocumentNumber('012345678'))
                ),
            ],
            'SecondFactorVettedWithoutTokenProofOfPossession:support-new-event-with-vetting-type' => [
                '{"identity_id":"b260f10b-ce7c-4d09-b6a4-50a3923d637f","name_id":"urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1","identity_institution":"institution-d.example.com","second_factor_id":"512de1ff-0ae0-41b7-bb21-b71d77e570b8","second_factor_type":"yubikey","preferred_locale":"nl_NL"}',
                '{"common_name":"jane-d1 Institution-D.EXAMPLE.COM","email":"jane+jane-d1@stepup.example.com","second_factor_type":"yubikey","second_factor_identifier":"123465293846985","vetting_type":{"type":"on-premise","document_number":"012345678"}}',
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    new IdentityId('b260f10b-ce7c-4d09-b6a4-50a3923d637f'),
                    new NameId('urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1'),
                    new Institution('institution-d.example.com'),
                    new SecondFactorId('512de1ff-0ae0-41b7-bb21-b71d77e570b8'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('123465293846985'),
                    new CommonName('jane-d1 Institution-D.EXAMPLE.COM'),
                    new Email('jane+jane-d1@stepup.example.com'),
                    new Locale('nl_NL'),
                    new OnPremiseVettingType(new DocumentNumber('012345678'))
                ),
            ],
            'SecondFactorVettedWithoutTokenProofOfPossession:support-old-event-with-document-number' => [
                '{"identity_id":"b260f10b-ce7c-4d09-b6a4-50a3923d637f","name_id":"urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1","identity_institution":"institution-d.example.com","second_factor_id":"512de1ff-0ae0-41b7-bb21-b71d77e570b8","second_factor_type":"yubikey","preferred_locale":"nl_NL"}',
                '{"common_name":"jane-d1 Institution-D.EXAMPLE.COM","email":"jane+jane-d1@stepup.example.com","second_factor_type":"yubikey","second_factor_identifier":"123465293846985","document_number":"012345678"}',
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    new IdentityId('b260f10b-ce7c-4d09-b6a4-50a3923d637f'),
                    new NameId('urn:collab:person:Institution-D.EXAMPLE.COM:jane-d1'),
                    new Institution('institution-d.example.com'),
                    new SecondFactorId('512de1ff-0ae0-41b7-bb21-b71d77e570b8'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('123465293846985'),
                    new CommonName('jane-d1 Institution-D.EXAMPLE.COM'),
                    new Email('jane+jane-d1@stepup.example.com'),
                    new Locale('nl_NL'),
                    new OnPremiseVettingType(new DocumentNumber('012345678'))
                ),
            ],
        ];
    }

    private static function UUID(): string
    {
        return (string) Uuid::uuid4();
    }
}

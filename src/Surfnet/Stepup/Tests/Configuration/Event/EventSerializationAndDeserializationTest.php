<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Tests\Configuration\Event;

use Broadway\Serializer\Serializable as SerializableInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Configuration;
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\EmailTemplatesUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\IdentityProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\SelfVetOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\SsoOn2faOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SsoRegistrationBypassOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\SsoRegistrationBypassOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupBundle\Value\SecondFactorType;

class EventSerializationAndDeserializationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('institutionConfigurationEventsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function an_event_should_be_the_same_after_serialization_and_deserialization(
        SerializableInterface $unserializedEvent,
    ): void {
        $serializedEvent = $unserializedEvent->serialize();

        $deserializedEvent = $unserializedEvent::deserialize($serializedEvent);

        $this->assertEquals($unserializedEvent, $deserializedEvent);
    }

    public static function institutionConfigurationEventsProvider(): array
    {
        $institution = new Institution('A test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $uuid = (string)Uuid::uuid4();

        return [
            // Configuration
            'NewConfigurationCreatedEvent' => [
                new NewConfigurationCreatedEvent(
                    Configuration::CONFIGURATION_ID,
                ),
            ],
            'ConfigurationUpdatedEvent' => [
                new ConfigurationUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['configurationKey' => 'configurationValue'],
                ),
            ],
            'EmailTemplatesUpdatedEvent' => [
                new EmailTemplatesUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['template'],
                ),
            ],
            'IdentityProvidersUpdatedEvent' => [
                new IdentityProvidersUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['idp'],
                ),
            ],
            'ServiceProvidersUpdatedEvent' => [
                new ServiceProvidersUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['sp'],
                ),
            ],
            'SraaUpdatedEvent' => [
                new SraaUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['sraa'],
                ),
            ],

            // InstitutionConfiguration
            'NewInstitutionConfigurationCreatedEvent' => [
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new UseRaLocationsOption(true),
                    new ShowRaaContactInformationOption(true),
                    new VerifyEmailOption(true),
                    new NumberOfTokensPerIdentityOption(0),
                    new SsoOn2faOption(false),
                    new SsoRegistrationBypassOption(false),
                    new SelfVetOption(true),
                    new SelfAssertedTokensOption(true),
                ),
            ],
            'UseRaLocationsOptionChangedEvent' => [
                new UseRaLocationsOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new UseRaLocationsOption(true),
                ),
            ],
            'ShowRaaContactInformationOptionChangedEvent' => [
                new ShowRaaContactInformationOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new ShowRaaContactInformationOption(true),
                ),
            ],
            'VerifyEmailOptionChangedEvent' => [
                new VerifyEmailOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new VerifyEmailOption(true),
                ),
            ],
            'SelfVetOptionChangedEvent' => [
                new SelfVetOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new SelfVetOption(false),
                ),
            ],
            'SsoOn2faOptionChangedEvent' => [
                new SsoOn2faOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new SsoOn2faOption(false),
                ),
            ],
            'SsoRegistrationBypassOptionChangedEvent' => [
                new SsoRegistrationBypassOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new SsoRegistrationBypassOption(false),
                ),
            ],
            'AllowedSecondFactorListUpdatedEvent:withSecondFactors' => [
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    AllowedSecondFactorList::ofTypes([
                        new SecondFactorType('yubikey'),
                        new SecondFactorType('sms'),
                    ]),
                ),
            ],
            'AllowedSecondFactorListUpdatedEvent:blank' => [
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    AllowedSecondFactorList::blank(),
                ),
            ],
            'RaLocationAddedEvent' => [
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($uuid),
                    new RaLocationName('Test name'),
                    new Location('Test location'),
                    new ContactInformation('Test contact information'),
                ),
            ],
            'RaLocationRenamedEvent' => [
                new RaLocationRenamedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new RaLocationName('Test name'),
                ),
            ],
            'RaLocationRelocatedEvent' => [
                new RaLocationRelocatedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new Location('Test location'),
                ),
            ],
            'RaLocationContactInformationChangedEvent' => [
                new RaLocationContactInformationChangedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new ContactInformation('Test contact information'),
                ),
            ],
            'RaLocationRemovedEvent' => [
                new RaLocationRemovedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                ),
            ],
            'InstitutionConfigurationRemovedEvent' => [
                new InstitutionConfigurationRemovedEvent(
                    $institutionConfigurationId,
                    new Institution('Babelfish Inc'),
                ),
            ],
        ];
    }
}

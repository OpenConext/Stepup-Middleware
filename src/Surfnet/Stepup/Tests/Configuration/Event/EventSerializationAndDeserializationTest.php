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

use Broadway\Serializer\SerializableInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Configuration;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\EmailTemplatesUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\IdentityProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionsWithRaLocationsUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class EventSerializationAndDeserializationTest extends TestCase
{
    /**
     * @test
     * @group domain
     *
     * @dataProvider institutionConfigurationEventsProvider
     */
    public function an_event_should_be_the_same_after_serialization_and_deserialization(SerializableInterface $unserializedEvent)
    {
        $serializedEvent = $unserializedEvent->serialize();

        $deserializedEvent = $unserializedEvent::deserialize($serializedEvent);

        $this->assertEquals($unserializedEvent, $deserializedEvent);
    }

    /**
     * @return SerializableInterface[]
     */
    public function institutionConfigurationEventsProvider()
    {
        $institution = new Institution('A test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $uuid = (string) Uuid::uuid4();

        return [
            // Configuration
            'NewConfigurationCreatedEvent' => [
                new NewConfigurationCreatedEvent(
                    Configuration::CONFIGURATION_ID
                )
            ],
            'ConfigurationUpdatedEvent' => [
                new ConfigurationUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['configurationKey' => 'configurationValue']
                )
            ],
            'EmailTemplatesUpdatedEvent' => [
                new EmailTemplatesUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['template']
                )
            ],
            'IdentityProvidersUpdatedEvent' => [
                new IdentityProvidersUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['idp']
                )
            ],
            'ServiceProvidersUpdatedEvent' => [
                new ServiceProvidersUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['sp']
                )
            ],
            'SraaUpdatedEvent' => [
                new SraaUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['sraa']
                )
            ],
            'InstitutionsWithRaLocationsUpdatedEvent' => [
                new InstitutionsWithRaLocationsUpdatedEvent(
                    Configuration::CONFIGURATION_ID,
                    ['some.institution.test']
                )
            ],

            // InstitutionConfiguration
            'NewInstitutionConfigurationCreatedEvent' => [
                new NewInstitutionConfigurationCreatedEvent($institutionConfigurationId, $institution)
            ],
            'RaLocationAddedEvent' => [
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($uuid),
                    new RaLocationName('Test name'),
                    new Location('Test location'),
                    new ContactInformation('Test contact information')
                )
            ],
            'RaLocationRenamedEvent' => [
                new RaLocationRenamedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new RaLocationName('Test name')
                )
            ],
            'RaLocationRelocatedEvent' => [
                new RaLocationRelocatedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new Location('Test location')
                )
            ],
            'RaLocationContactInformationChangedEvent' => [
                new RaLocationContactInformationChangedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid),
                    new ContactInformation('Test contact information')
                )
            ],
            'RaLocationRemovedEvent'   => [
                new RaLocationRemovedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($uuid)
                )
            ],
        ];
    }
}

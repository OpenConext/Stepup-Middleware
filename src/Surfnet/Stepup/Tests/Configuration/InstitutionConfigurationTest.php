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

namespace Surfnet\Stepup\Tests\Configuration;

use Broadway\Domain\DomainMessage;
use PHPUnit_Framework_TestCase as TestCase;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class InstitutionConfigurationTest extends TestCase
{
    /**
     * @test
     * @group aggregate
     */
    public function creating_a_new_institution_configuration_leads_to_an_new_institution_configuration_created_event()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $expectedEvents = [new NewInstitutionConfigurationCreatedEvent($institutionConfigurationId, $institution)];

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $actualEvents = $this->getEventsFrom($institutionConfiguration);

        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @return string
     */
    private static function uuid()
    {
        return (string) Uuid::uuid4();
    }

    private function getEventsFrom(InstitutionConfiguration $institutionConfiguration)
    {
        return array_map(function (DomainMessage $domainMessage) {
            return $domainMessage->getPayload();
        }, iterator_to_array($institutionConfiguration->getUncommittedEvents()));
    }
}

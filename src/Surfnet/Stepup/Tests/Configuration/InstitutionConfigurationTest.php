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

use PHPUnit_Framework_TestCase as TestCase;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationList;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class InstitutionConfigurationTest extends TestCase
{
    /**
     * @test
     * @group aggregate
     */
    public function an_institution_configuration_can_be_created_with_a_given_institution_configuration_id_and_a_given_institution()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $expectedRaLocations = new RaLocationList([]);

        $this->assertEquals($institutionConfigurationId, $institutionConfiguration->getInstitutionConfigurationId());
        $this->assertEquals($institution, $institutionConfiguration->getInstitution());
        $this->assertEquals($expectedRaLocations, $institutionConfiguration->getRaLocations());
    }

    /**
     * @test
     * @group aggregate
     */
    public function an_ra_location_can_be_added_to_an_institution_configuration()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $raLocationId       = new RaLocationId(self::uuid());
        $raLocationName     = new RaLocationName('An RA location name');
        $location           = new Location('A location');
        $contactInformation = new ContactInformation('Contact information');

        $expectedRaLocations = new RaLocationList(
            [
                RaLocation::create(
                    $raLocationId,
                    $raLocationName,
                    $location,
                    $contactInformation
                ),
            ]
        );

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $institutionConfiguration->addRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        );

        $actualRaLocations = $institutionConfiguration->getRaLocations();

        $this->assertEquals($expectedRaLocations, $actualRaLocations);
    }

    /**
     * @test
     * @group aggregate
     */
    public function an_ra_location_can_be_renamed()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $raLocationId       = new RaLocationId(self::uuid());
        $raLocationName     = new RaLocationName('An RA location name');
        $location           = new Location('A location');
        $contactInformation = new ContactInformation('Contact information');

        $newRaLocationName = new RaLocationName('Renamed RA location');
        $expectedRaLocations = new RaLocationList(
            [
                RaLocation::create(
                    $raLocationId,
                    $newRaLocationName,
                    $location,
                    $contactInformation
                ),
            ]
        );

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $institutionConfiguration->addRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        );
        $institutionConfiguration->changeRaLocation(
            $raLocationId,
            $newRaLocationName,
            $location,
            $contactInformation
        );

        $actualRaLocations = $institutionConfiguration->getRaLocations();

        $this->assertEquals($expectedRaLocations, $actualRaLocations);
    }

    /**
     * @test
     * @group aggregate
     */
    public function an_ra_location_can_be_relocated()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $raLocationId       = new RaLocationId(self::uuid());
        $raLocationName     = new RaLocationName('An RA location name');
        $location           = new Location('A location');
        $contactInformation = new ContactInformation('Contact information');

        $newLocation         = new Location('Relocated RA location');
        $expectedRaLocations = new RaLocationList(
            [
                RaLocation::create(
                    $raLocationId,
                    $raLocationName,
                    $newLocation,
                    $contactInformation
                ),
            ]
        );

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $institutionConfiguration->addRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        );
        $institutionConfiguration->changeRaLocation(
            $raLocationId,
            $raLocationName,
            $newLocation,
            $contactInformation
        );

        $actualRaLocations = $institutionConfiguration->getRaLocations();

        $this->assertEquals($expectedRaLocations, $actualRaLocations);
    }

    /**
     * @test
     * @group aggregate
     */
    public function an_ra_locations_contact_information_can_be_changed()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $raLocationId       = new RaLocationId(self::uuid());
        $raLocationName     = new RaLocationName('An RA location name');
        $location           = new Location('A location');
        $contactInformation = new ContactInformation('Contact information');

        $newContactInformation = new ContactInformation('RA location with changed ContactInformation');
        $expectedRaLocations   = new RaLocationList(
            [
                RaLocation::create(
                    $raLocationId,
                    $raLocationName,
                    $location,
                    $newContactInformation
                ),
            ]
        );

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $institutionConfiguration->addRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        );
        $institutionConfiguration->changeRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $newContactInformation
        );

        $actualRaLocations = $institutionConfiguration->getRaLocations();

        $this->assertEquals($expectedRaLocations, $actualRaLocations);
    }

    /**
     * @test
     * @group aggregate
     */
    public function an_ra_location_can_be_removed()
    {
        $institution = new Institution('Test institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $raLocationId       = new RaLocationId(self::uuid());
        $raLocationName     = new RaLocationName('An RA location name');
        $location           = new Location('A location');
        $contactInformation = new ContactInformation('Contact information');

        $expectedRaLocations   = new RaLocationList([]);

        $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        $institutionConfiguration->addRaLocation(
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        );
        $institutionConfiguration->removeRaLocation(
            $raLocationId
        );

        $actualRaLocations = $institutionConfiguration->getRaLocations();

        $this->assertEquals($expectedRaLocations, $actualRaLocations);
    }

    /**
     * @return string
     */
    private static function uuid()
    {
        return (string) Uuid::uuid4();
    }
}

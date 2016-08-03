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

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\RaLocationList;

class RaLocationListTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function an_ra_location_list_does_not_allow_ra_locations_with_the_same_ra_location_id_upon_creation()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\LogicException', 'Cannot add');

        $raLocations = $this->getRaLocationsArray();
        $existingRaLocation = $raLocations[0];

        array_push($raLocations, $existingRaLocation);
        new RaLocationList($raLocations);
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_list_does_not_allow_adding_ra_locations_with_an_ra_location_id_that_is_already_present()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\LogicException', 'Cannot add');

        $raLocations = $this->getRaLocationsArray();
        $existingRaLocation = $raLocations[0];

        $raLocationList = new RaLocationList($raLocations);
        $raLocationList->add($existingRaLocation);
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_list_is_created_from_ra_locations()
    {
        $raLocations = $this->getRaLocationsArray();

        $raLocationList = new RaLocationList($raLocations);
        $raLocationListAsArray = iterator_to_array($raLocationList);

        $this->assertEquals($raLocations, $raLocationListAsArray);
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_list_has_an_ra_location_with_a_given_ra_location_id()
    {
        $raLocations = $this->getRaLocationsArray();
        $expectedRaLocationIdToBePresent = $raLocations[0]->getId();

        $raLocationList = new RaLocationList($raLocations);

        $this->assertTrue($raLocationList->containsWithId($expectedRaLocationIdToBePresent));
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_list_does_not_have_ra_locations_with_a_non_present_ra_location_id()
    {
        $raLocations = $this->getRaLocationsArray();
        $expectedRaLocationIdNotToBePresent = new RaLocationId((string) Uuid::uuid4());

        $raLocationList = new RaLocationList($raLocations);

        $this->assertFalse($raLocationList->containsWithId($expectedRaLocationIdNotToBePresent));
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_is_added_to_an_ra_location_list()
    {
        $raLocations = $this->getRaLocationsArray();

        $raLocationList = new RaLocationList([]);
        $raLocationList->add($raLocations[0]);

        $raLocationListAsArray = iterator_to_array($raLocationList);

        $this->assertEquals($raLocations[0], $raLocationListAsArray[0]);
    }

    /**
     * @test
     * @group domain
     */
    public function an_ra_location_is_removed_from_an_ra_location_list_by_its_ra_location_id()
    {
        $raLocations = $this->getRaLocationsArray();
        $raLocationToRemove = $raLocations[0];

        $raLocationList = new RaLocationList($raLocations);
        $raLocationList->removeWithId($raLocationToRemove->getId());

        $raLocationListAsArray = iterator_to_array($raLocationList);
        $expectOnlyTheSecondRaLocation = array_splice($raLocations, -1);

        $this->assertEquals($expectOnlyTheSecondRaLocation, $raLocationListAsArray);
    }

    protected function getRaLocationsArray()
    {
        return [
            RaLocation::create(
                new RaLocationId((string) Uuid::uuid4()),
                new RaLocationName('An RA location name'),
                new Location('A location'),
                new ContactInformation('Contact Information')
            ),
            RaLocation::create(
                new RaLocationId((string) Uuid::uuid4()),
                new RaLocationName('Another RA location name'),
                new Location('Another location'),
                new ContactInformation('Some more contact Information')
            ),
        ];
    }
}

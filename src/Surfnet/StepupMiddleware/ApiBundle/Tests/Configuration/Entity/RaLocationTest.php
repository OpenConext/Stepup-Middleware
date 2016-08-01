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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\Entity;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;

class RaLocationTest extends TestCase
{
    /**
     * @test
     * @group entity
     */
    public function serialized_ra_location_has_the_correct_keys()
    {
        $raLocation = RaLocation::create(
            'An id',
            new Institution('An institution'),
            new RaLocationName('An RA location'),
            new Location('A location'),
            new ContactInformation('Contact information')
        );

        $serialized   = json_encode($raLocation);
        $deserialized = json_decode($serialized, true);

        $expectedKeys = ['id', 'institution', 'name', 'location', 'contact_information'];

        $this->assertCount(
            count($expectedKeys),
            $deserialized,
            'Serialized RaLocation does not have the expected amount of keys'
        );

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $deserialized, sprintf('Serialized RaLocation is missing key "%s"', $key));
        }
    }

    /**
     * @test
     * @group entity
     */
    public function serialized_ra_location_has_the_correct_values()
    {
        $raLocation = RaLocation::create(
            'An id',
            new Institution('An institution'),
            new RaLocationName('An RA location'),
            new Location('A location'),
            new ContactInformation('Contact information')
        );

        $serialized   = json_encode($raLocation);
        $deserialized = json_decode($serialized, true);

        $this->assertSame($raLocation->id, $deserialized['id']);
        $this->assertSame($raLocation->institution->getInstitution(), $deserialized['institution']);
        $this->assertSame($raLocation->name->getRaLocationName(), $deserialized['name']);
        $this->assertSame($raLocation->location->getLocation(), $deserialized['location']);
        $this->assertSame(
            $raLocation->contactInformation->getContactInformation(),
            $deserialized['contact_information']
        );
    }
}

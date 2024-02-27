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

use PHPUnit\Framework\TestCase as TestCase;
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
    public function an_ra_location_is_correctly_serialized_to_json(): void
    {
        $deserializedRaLocation = [
            'id'                  => 'An id',
            'institution'         => 'surfnet.nl',
            'name'                => 'An RA location',
            'location'            => 'A location',
            'contact_information' => 'Contact information',
        ];

        $raLocation = RaLocation::create(
            $deserializedRaLocation['id'],
            new Institution($deserializedRaLocation['institution']),
            new RaLocationName($deserializedRaLocation['name']),
            new Location($deserializedRaLocation['location']),
            new ContactInformation($deserializedRaLocation['contact_information'])
        );

        $expectedSerialization = json_encode($deserializedRaLocation);
        $actualSerialization   = json_encode($raLocation);

       $this->assertSame($expectedSerialization, $actualSerialization);
    }
}

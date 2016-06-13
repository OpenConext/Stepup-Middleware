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

use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Configuration\Value\Location;

class LocationTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider invalidValueProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function it_cannot_be_created_with_anything_but_a_nonempty_string($invalidValue)
    {
        new Location($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_locations_with_the_same_value_are_equal()
    {
        $location          = new Location('a');
        $theSame           = new Location('a');
        $theSameWithSpaces = new Location('  a ');
        $different         = new Location('A');

        $this->assertTrue($location->equals($theSame));
        $this->assertTrue($location->equals($theSameWithSpaces));
        $this->assertFalse($location->equals($different));
    }

    /**
     * dataprovider
     */
    public function invalidValueProvider()
    {
        return [
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}

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
use Surfnet\Stepup\Configuration\Value\Institution;

class InstitutionTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider nonStringOrNonEmptyStringProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function an_institution_cannot_be_created_with_anything_but_a_nonempty_string($invalidValue)
    {
        new Institution($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_institutions_with_the_same_value_are_equal()
    {
        $institution       = new Institution('a');
        $theSame           = new Institution('a');
        $theSameWithSpaces = new Institution('  a ');
        $different         = new Institution('A');

        $this->assertTrue($institution->equals($theSame));
        $this->assertTrue($institution->equals($theSameWithSpaces));
        $this->assertTrue($institution->equals($different));
    }

    public function nonStringOrNonEmptyStringProvider()
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}

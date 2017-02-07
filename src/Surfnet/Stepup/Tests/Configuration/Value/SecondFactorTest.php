<?php

/**
 * Copyright 2017 SURFnet B.V.
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
use Surfnet\Stepup\Configuration\Value\SecondFactor;

class SecondFactorTest extends TestCase
{
    /**
     * @test
     * @group        domain
     * @dataProvider nonStringProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $nonString
     */
    public function a_second_factor_cannot_be_created_with_anything_but_a_string($nonString)
    {
        new SecondFactor($nonString);
    }

    /**
     * @test
     * @group domain
     */
    public function two_second_factor_names_with_the_same_values_are_equal()
    {
        $secondFactor = new SecondFactor('sms');
        $theSame      = new SecondFactor('sms');

        $this->assertTrue($secondFactor->equals($theSame));
    }

    /**
     * @test
     * @group domain
     */
    public function two_second_factor_names_with_different_values_are_not_equal()
    {
        $secondFactor = new SecondFactor('sms');
        $different    = new SecondFactor('yubikey');

        $this->assertFalse($secondFactor->equals($different));
    }

    public function nonStringProvider()
    {
        return [
            'null'         => [null],
            'boolean'      => [false],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}

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
use stdClass;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\SecondFactor;

class AllowedSecondFactorListTest extends TestCase
{
    /**
     * @test
     * @group domain
     * @dataProvider nonSecondFactorProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param $nonSecondFactor
     */
    public function an_allowed_second_factor_list_can_only_contain_second_factors($nonSecondFactor)
    {
        new AllowedSecondFactorList([$nonSecondFactor]);
    }

    /**
     * @test
     * @group domain
     */
    public function an_empty_allowed_second_factor_list_allows_all_second_factors()
    {
        $allowedSecondFactorList = new AllowedSecondFactorList([]);
        $secondFactor = new SecondFactor('sms');

        $isSecondFactorAllowed = $allowedSecondFactorList->isAllowed($secondFactor);

        $this->assertTrue(
            $isSecondFactorAllowed,
            'An empty allowed second factor list should allow all second factors but it does not'
        );
    }

    /**
     * @test
     * @group domain
     */
    public function a_second_factor_on_the_allowed_second_factor_list_is_allowed()
    {
        $allowedSecondFactorList = new AllowedSecondFactorList([new SecondFactor('sms')]);
        $allowedSecondFactor = new SecondFactor('sms');

        $isSecondFactorAllowed = $allowedSecondFactorList->isAllowed($allowedSecondFactor);

        $this->assertTrue(
            $isSecondFactorAllowed,
            'An allowed second factor list should allow a listed second factor but it does not'
        );
    }

    /**
     * @test
     * @group domain
     */
    public function a_second_factor_not_on_the_allowed_second_factor_list_is_not_allowed()
    {
        $allowedSecondFactorList = new AllowedSecondFactorList([new SecondFactor('sms')]);
        $disallowedSecondFactor = new SecondFactor('yubikey');

        $isSecondFactorAllowed = $allowedSecondFactorList->isAllowed($disallowedSecondFactor);

        $this->assertFalse(
            $isSecondFactorAllowed,
            'An allowed second factor list should not allow an unlisted second factor but it does not'
        );
    }

    public function nonSecondFactorProvider()
    {
        return [
            'null' => [null],
            'boolean' => [false],
            'integer' => [1],
            'float' => [1.23],
            'string' => ['second factor'],
            'object' => [new stdClass()],
            'array' => [[]],
        ];
    }
}

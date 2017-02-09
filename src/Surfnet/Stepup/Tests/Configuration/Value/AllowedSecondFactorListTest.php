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
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\StepupBundle\Value\SecondFactorType;

class AllowedSecondFactorListTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function an_empty_allowed_second_factor_list_allows_all_second_factors()
    {
        $allowedSecondFactorList = AllowedSecondFactorList::blank([]);
        $secondFactor            = new SecondFactorType('sms');

        $isSecondFactorAllowed = $allowedSecondFactorList->allows($secondFactor);

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
        $allowedSecondFactorList = AllowedSecondFactorList::ofTypes([new SecondFactorType('sms')]);
        $allowedSecondFactor     = new SecondFactorType('sms');

        $isSecondFactorAllowed = $allowedSecondFactorList->allows($allowedSecondFactor);

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
        $allowedSecondFactorList = AllowedSecondFactorList::ofTypes([new SecondFactorType('sms')]);
        $disallowedSecondFactor  = new SecondFactorType('yubikey');

        $isSecondFactorAllowed = $allowedSecondFactorList->allows($disallowedSecondFactor);

        $this->assertFalse(
            $isSecondFactorAllowed,
            'An allowed second factor list should not allow an unlisted second factor but it does not'
        );
    }
}
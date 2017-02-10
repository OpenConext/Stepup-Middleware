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
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorMap;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupBundle\Value\SecondFactorType;

class AllowedSecondFactorMapTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function an_allowed_second_factor_map_maps_allowed_second_factor_lists_to_institutions()
    {
        $institutionA = new Institution('institution-with-filled-list.test');
        $institutionB = new Institution('institution-with-blank-list.test');
        $institutions = [$institutionA, $institutionB];

        $allowedSecondFactorsForA = [new SecondFactorType('sms'), new SecondFactorType('yubikey')];

        $allowedSecondFactorMap = AllowedSecondFactorMap::mappedTo($institutions);

        foreach ($allowedSecondFactorsForA as $allowedSecondFactor) {
            $allowedSecondFactorMap->institutionAllows($institutionA, $allowedSecondFactor);
        }

        $expectedAllowedSecondFactorListForA = AllowedSecondFactorList::ofTypes($allowedSecondFactorsForA);
        $actualAllowedSecondFactorListForA = $allowedSecondFactorMap->getSecondFactorListFor($institutionA);
        $isSameAllowedSecondFactorListForA = $actualAllowedSecondFactorListForA->equals(
            $expectedAllowedSecondFactorListForA
        );

        $this->assertTrue(
            $isSameAllowedSecondFactorListForA,
            'The map should have returned a filled AllowedSecondFactorList for InstitutionA but it did not'
        );

        $expectedAllowedSecondFactorListForB = AllowedSecondFactorList::blank();
        $actualAllowedSecondFactorListForB = $allowedSecondFactorMap->getSecondFactorListFor($institutionB);
        $isSameAllowedSecondFactorListForB = $actualAllowedSecondFactorListForB->equals(
            $expectedAllowedSecondFactorListForB
        );

        $this->assertTrue(
            $isSameAllowedSecondFactorListForB,
            'The map should have returned a blank AllowedSecondFactorList for InstitutionB but it did not'
        );

    }
}

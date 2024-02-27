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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\Service;

use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\AllowedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorMap;

class AllowedSecondFactorMapTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function an_allowed_second_factor_that_contains_a_given_institution_will_result_in_a_filled_allowed_second_factor_list(): void
    {
        $institution = new Institution('institution-with-filled-list.test');
        $allowedSecondFactors = [
            AllowedSecondFactor::createFrom($institution, new SecondFactorType('sms')),
            AllowedSecondFactor::createFrom($institution, new SecondFactorType('yubikey')),
        ];

        $allowedSecondFactorMap = AllowedSecondFactorMap::from($allowedSecondFactors);

        $expectedAllowedSecondFactorList = AllowedSecondFactorList::ofTypes([
            new SecondFactorType('sms'),
            new SecondFactorType('yubikey'),
        ]);
        $actualAllowedSecondFactorList = $allowedSecondFactorMap->getAllowedSecondFactorListFor($institution);

        $this->assertTrue($expectedAllowedSecondFactorList->equals($actualAllowedSecondFactorList));
    }

    /**
     * @test
     * @group domain
     */
    public function an_allowed_second_factor_map_that_does_not_contain_a_given_institution_will_result_in_a_blank_allowed_second_factor_list(): void
    {
        $institution = new Institution('institution-with-blank-list.test');
        $allowedSecondFactors = [];

        $allowedSecondFactorMap = AllowedSecondFactorMap::from($allowedSecondFactors);

        $expectedAllowedSecondFactorList = AllowedSecondFactorList::blank();
        $actualAllowedSecondFactorList = $allowedSecondFactorMap->getAllowedSecondFactorListFor($institution);

        $this->assertTrue($expectedAllowedSecondFactorList->equals($actualAllowedSecondFactorList));
    }
}

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


use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOptionMap;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

class InstitutionAuthorizationOptionMapTest extends TestCase
{
    /**
     * @var Institution
     */
    private $institution;

    public function setUp()
    {
        $this->institution = new Institution('inst');
    }

    /**
     * @test
     * @group domain
     */
    public function an_array_initialized_with_authorizations_should_return_valid_institutions_per_role()
    {
        $testData = [
            ['inst ', 'inst', 'use_ra'],
            ['inst ', 'instb', 'use_ra'],
            ['instb ', 'instc', 'use_ra'],
            ['instb ', 'insta', 'use_ra'],
            ['inst ', 'insta', 'use_raa'],
            ['instb ', 'insta', 'use_raa'],
            ['inst ', 'instb', 'use_raa'],
            ['inst ', 'insta', 'use_ra'],
            ['inst ', 'insta', 'select_raa'],
        ];

        $institutionAuthorizations = [];
        foreach ($testData as $data) {
            $institutionAuthorizations[] = InstitutionAuthorization::create(new Institution($data[0]), new Institution($data[1]), new InstitutionRole($data[2]));
        }

        $institutionAuthorizationMap = InstitutionAuthorizationOptionMap::fromInstitutionAuthorizations($this->institution, $institutionAuthorizations);

        $this->assertEquals(['inst','insta','instb'], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::useRa())->getInstitutions($this->institution));
        $this->assertEquals(['insta','instb'], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::useRaa())->getInstitutions($this->institution));
        $this->assertEquals(['insta'], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::selectRaa())->getInstitutions($this->institution));
    }

    /**
     * @test
     * @group domain
     */
    public function an_array_initialized_with_no_authorizations_should_return_valid_institutions_per_role()
    {
        $institutionAuthorizationMap = InstitutionAuthorizationOptionMap::fromInstitutionAuthorizations($this->institution, []);

        $this->assertEquals([], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::useRa())->getInstitutions($this->institution));
        $this->assertEquals([], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::useRaa())->getInstitutions($this->institution));
        $this->assertEquals([], $institutionAuthorizationMap->getAuthorizationOptionsByRole(InstitutionRole::selectRaa())->getInstitutions($this->institution));
    }
}
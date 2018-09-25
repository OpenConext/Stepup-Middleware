<?php

/**
 * Copyright 2018 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not select this file except in compliance with the License.
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
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionOption;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\InstitutionSet;
use Surfnet\Stepup\Configuration\Value\SelectRaaOption;

class InstitutionRoleTest extends TestCase
{
    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function invalid_types_are_rejected_during_construction($arguments)
    {
        InstitutionRole::create($arguments);
    }

    /**
     * @test
     * @group domain
     * @dataProvider institutionTypeProvider
     */
    public function institution_roles_can_be_created_by_type($type)
    {
        $role1 = InstitutionRole::create($type);
        $role2 = InstitutionRole::create($type);

        $this->assertEquals($type, $role1->getType());
        $this->assertTrue($role1->equals($role2));
    }

    public function institutionTypeProvider()
    {
        return [
            'use_ra' => ['use_ra'],
            'use_raa' => ['use_raa'],
            'select_raa' => ['select_raa'],
        ];
    }

    public function invalidConstructorArgumentsProvider()
    {
        return [
            'cant-be-boolean' => [false],
            'cant-be-object' => ['invalid'],
            'cant-be-integer' => [42],
        ];
    }
}

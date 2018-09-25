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
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\InstitutionSet;
use Surfnet\Stepup\Configuration\Value\InstitutionOption;

class InstitutionOptionOptionTest extends TestCase
{
    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var InstitutionRole
     */
    private $institutionRole;

    public function setUp()
    {
        $this->institution = new Institution('inst');
        $this->institutionRole = InstitutionRole::useRa();
    }

    /**
     * @test
     * @group domain
     */
    public function institution_entries_are_sorted()
    {
        $useRaOption = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, ['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $useRaOption->getInstitutionSet()->getInstitutions());
    }

    /**
     * @test
     * @group domain
     */
    public function institution_entries_default_is_own_institution()
    {
        $useRaOption1 = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, null);
        $useRaOption2 = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, [$this->institution->getInstitution()]);
        $this->assertTrue($useRaOption1->equals($useRaOption2));
    }

    /**
     * @test
     * @group domain
     * @dataProvider institutionSetComparisonProvider
     */
    public function institution_option_instances_can_be_compared($expectation, $configurationA, $configurationB)
    {
        $useRaOption = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, $configurationA);
        $secondInstitutionOption = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, $configurationB);
        $this->assertEquals($expectation, $useRaOption->equals($secondInstitutionOption));
    }

    /**InstitutionOption
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable()
    {
        $institutionOption = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, ['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $institutionOption->getInstitutionSet()->jsonSerialize());
    }



    /**
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable_on_empty_set()
    {
        $institutionOption = InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution);
        $this->assertEquals([$this->institution], $institutionOption->getInstitutionSet()->jsonSerialize());
    }

    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function invalid_types_are_rejected_during_construction($arguments)
    {
        InstitutionOption::fromInstitutionConfig($this->institutionRole, $this->institution, $arguments);
    }

    /**
     * @test
     * @group domain
     */
    public function the_default_value_is_given_institution()
    {
        $this->assertEquals([$this->institution], InstitutionOption::getDefault($this->institutionRole, $this->institution)->getInstitutionSet()->getInstitutions());
    }

    public function institutionSetComparisonProvider()
    {
        return [
            'both-same-set-of-institutions' => [true, ['a', 'b'], ['a', 'b']],
            'both-null' => [true, null, null],
            'both-empty' => [true, [], []],
            'empty-vs-null' => [false, [], null],
            'set-of-institutions-vs-null' => [false, ['a', 'b'], null],
            'set-of-institutions-vs-empty' => [false, ['a', 'b'], []],
        ];
    }

    public function invalidConstructorArgumentsProvider()
    {
        return [
            'cant-be-boolean' => [false],
            'cant-be-object' => [InstitutionSet::createFromStringArray(['a', 'b'])],
            'cant-be-integer' => [42],
        ];
    }
}

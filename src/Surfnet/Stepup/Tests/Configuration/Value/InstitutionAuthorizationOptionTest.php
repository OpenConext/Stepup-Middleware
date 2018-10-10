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
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;

class InstitutionAuthorizationOptionTest extends TestCase
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
        $useRaOption = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, ['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $useRaOption->getInstitutions($this->institution));
    }

    /**
     * @test
     * @group domain
     */
    public function institution_entries_default_is_own_institution()
    {
        $useRaOption1 = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, null);
        $useRaOption2 = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, [$this->institution->getInstitution()]);
        $this->assertEquals([$this->institution], $useRaOption1->getInstitutions($this->institution));
        $this->assertEquals([$this->institution], $useRaOption2->getInstitutions($this->institution));
    }

    /**
     * @test
     * @group domain
     * @dataProvider institutionSetComparisonProvider
     */
    public function institution_option_instances_can_be_compared($expectation, $configurationA, $configurationB)
    {
        $useRaOption = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, $configurationA);
        $secondInstitutionOption = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, $configurationB);
        $this->assertEquals($expectation, $useRaOption->equals($secondInstitutionOption));
    }

    /**InstitutionAuthorizationOption
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable()
    {
        $institutionOption = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, ['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $institutionOption->jsonSerialize());
    }

    /**
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable_on_empty_set()
    {
        $institutionOption = InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole);
        $this->assertEquals(null, $institutionOption->jsonSerialize());
    }

    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function invalid_types_are_rejected_during_construction($arguments)
    {
        InstitutionAuthorizationOption::fromInstitutionConfig($this->institutionRole, $arguments);
    }
    /**
     * @test
     * @group domain
     */
    public function should_be_set_to_default_if_created_with_own_institution_as_institutions()
    {
        $institutions = [
            $this->institution,
        ];
        $option = InstitutionAuthorizationOption::fromInstitutions(InstitutionRole::useRa(), $this->institution, $institutions);

        $this->assertEquals([$this->institution], $option->getInstitutions($this->institution));
        $this->assertEquals(true, $option->isDefault());
        $this->assertEquals([], $option->getInstitutionSet()->toScalarArray());
    }

    /**
     * @test
     * @group domain
     */
    public function the_default_value_is_given_institution()
    {
        $this->assertEquals([$this->institution], InstitutionAuthorizationOption::getDefault($this->institutionRole)->getInstitutions($this->institution));
    }

    /**
     * @test
     * @group domain
     */
    public function the_empty_value_is_no_value()
    {
        $this->assertEquals([], InstitutionAuthorizationOption::getEmpty($this->institutionRole)->getInstitutions($this->institution));
    }

    /**
     * @test
     * @group domain
     */
    public function the_blank_method_should_return_null()
    {
        $this->assertEquals(null, InstitutionAuthorizationOption::blank());
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
            'cant-be-object' => [[new Institution('a'), new Institution('b')]],
            'cant-be-integer' => [42],
        ];
    }
}

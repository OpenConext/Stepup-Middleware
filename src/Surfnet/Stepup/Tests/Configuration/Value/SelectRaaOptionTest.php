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
use Surfnet\Stepup\Configuration\Value\InstitutionSet;
use Surfnet\Stepup\Configuration\Value\SelectRaaOption;

class SelectRaaOptionTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function institution_entries_are_sorted()
    {
        $selectRaaOption = new SelectRaaOption(['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $selectRaaOption->getInstitutions()->toScalarArray());
    }

    /**
     * @test
     * @group domain
     * @dataProvider institutionSetComparisonProvider
     */
    public function select_raa_option_instances_can_be_compared($expectation, $configurationA, $configurationB)
    {
        $selectRaaOption = new SelectRaaOption($configurationA);
        $secondSelectRaaOption = new SelectRaaOption($configurationB);
        $this->assertEquals($expectation, $selectRaaOption->equals($secondSelectRaaOption));
    }

    /**
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable()
    {
        $selectRaaOption = new SelectRaaOption(['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $selectRaaOption->jsonSerialize());
    }

    /**
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_serializable_on_null()
    {
        $useRaOption = SelectRaaOption::getDefault();
        $this->assertEquals(null, $useRaOption->jsonSerialize());
    }
    
    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function invalid_types_are_rejected_during_construction($arguments)
    {
        new SelectRaaOption($arguments);
    }

    /**
     * @test
     * @group domain
     */
    public function the_default_value_is_null()
    {
        $this->assertNull(SelectRaaOption::getDefault()->getInstitutions());
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
            'cant-be-object' => [InstitutionSet::fromInstitutionConfig(['a', 'b'])],
            'cant-be-integer' => [42],
        ];
    }
}
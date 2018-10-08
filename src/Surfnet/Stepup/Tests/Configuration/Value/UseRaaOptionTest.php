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
use Surfnet\Stepup\Configuration\Value\UseRaaOption;

class UseRaaOptionTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function institution_entries_are_sorted()
    {
        $useRaaOption = new UseRaaOption(['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $useRaaOption->getInstitutions()->toScalarArray());
    }

    /**
     * @test
     * @group domain
     * @dataProvider institutionSetComparisonProvider
     */
    public function select_raa_option_instances_can_be_compared($expectation, $configurationA, $configurationB)
    {
        $useRaaOption = new UseRaaOption($configurationA);
        $secondUseRaaOption = new UseRaaOption($configurationB);
        $this->assertEquals($expectation, $useRaaOption->equals($secondUseRaaOption));
    }

    /**
     * @test
     * @group domain
     */
    public function can_be_retrieved_json_encodable()
    {
        $useRaaOption = new UseRaaOption(['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $useRaaOption->jsonSerialize());
    }

    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function invalid_types_are_rejected_during_construction($arguments)
    {
        new UseRaaOption($arguments);
    }

    /**
     * @test
     * @group domain
     */
    public function the_default_value_is_null()
    {
        $this->assertNull(UseRaaOption::getDefault()->getInstitutions());
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

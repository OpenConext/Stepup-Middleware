<?php

/**
 * Copyright 2018 SURFnet B.V.
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

use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionSet;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class InstitutionSetTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function the_set_is_built_out_of_institution_vos(): void
    {
        $institutionA = new Institution('a');
        $institutionB = new Institution('b');
        $institutionC = new Institution('C');

        $set = InstitutionSet::create([$institutionA, $institutionB, $institutionC]);
        $this->assertTrue(is_array($set->toScalarArray()));
    }

    /**
     * @test
     * @group domain
     */
    public function duplicate_entries_are_not_allowed(): void
    {
        $this->expectExceptionMessage("Duplicate entries are not allowed in the InstitutionSet");
        $this->expectException(InvalidArgumentException::class);

        $institutionB = new Institution('b');
        $institutionBDupe = new Institution('b');

        InstitutionSet::create([$institutionB, $institutionBDupe]);
    }

    /**
     * @test
     * @group domain
     */
    public function duplicate_entries_are_not_allowed_case_insensitive(): void
    {
        $this->expectExceptionMessage("Duplicate entries are not allowed in the InstitutionSet");
        $this->expectException(InvalidArgumentException::class);

        $institutionB = new Institution('b');
        $institutionBDupe = new Institution('B');

        InstitutionSet::create([$institutionB, $institutionBDupe]);
    }

    /**
     * @test
     * @group domain
     */
    public function only_institutions_can_be_present_in_set(): void
    {
        $this->expectExceptionMessage(
            "Invalid argument type: \"Surfnet\Stepup\Configuration\Value\Institution\" expected, \"Surfnet\Stepup\Configuration\Value\Location\" given for \"institutions\"",
        );
        $this->expectException(InvalidArgumentException::class);

        $institution = new Institution('b');
        $location = new Location('Foobar');

        InstitutionSet::create([$institution, $location]);
    }

    /**
     * @test
     * @group domain
     */
    public function factory_method_can_build_from_empty_array(): void
    {
        $input = [];
        $set = InstitutionSet::create($input);
        $this->assertEmpty($set->toScalarArray());
    }

    /**
     * @test
     * @group domain
     */
    public function factory_method_can_build_from_array_of_string(): void
    {
        $input = [
            new Institution('a'),
            new Institution('b'),
            new Institution('c'),
            new Institution('d'),
        ];
        $set = InstitutionSet::create($input);
        $this->assertEquals(
            $input,
            $set->toScalarArray(),
        );
    }

    /**
     * This test actually tests the Institution's input validation during construction time
     *
     * @test
     * @group domain
     * @dataProvider dirtyInstitutionListProvider
     *
     */
    public function factory_method_can_build_from_array_of_string_and_rejects_invalid_types(array $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        InstitutionSet::create($invalid);
    }

    /**
     * @test
     * @group domain
     */
    public function sets_can_be_compared(): void
    {
        $input = [
            new Institution('a'),
            new Institution('b'),
            new Institution('c'),
            new Institution('d'),
        ];
        $set = InstitutionSet::create($input);
        $secondSet = InstitutionSet::create($input);
        $this->assertTrue($set->equals($secondSet));
    }

    public function dirtyInstitutionListProvider(): array
    {
        return [
            'numeric_entry' => [['a', 1, 'b']],
            'array_entry' => [['a', 'b', []]],
            'bolean_entry' => [[false, 'a', 'b']],
            'non_scalar_entry' => [['a', 'b', new Location('location x')]],
        ];
    }
}

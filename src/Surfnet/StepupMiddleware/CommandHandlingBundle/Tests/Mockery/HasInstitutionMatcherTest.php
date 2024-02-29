<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Exception\RuntimeException;
use PHPUnit\Framework\TestCase as TestCase;
use stdClass;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery\TestObjects\ObjectWithInstitutionAccessor;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery\TestObjects\ObjectWithInstitutionProperty;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery\TestObjects\ObjectWithoutInstitutionPropertyAndAccessor;

class HasInstitutionMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group mockery
     * @group institution
     *
     * @dataProvider nonStringProvider
     */
    public function has_institution_matcher_only_matches_against_strings(bool|int|float|stdClass|array|null $nonString,): void
    {
        $this->expectException(RuntimeException::class);

        $hasInstitutionMatcher = new HasInstitutionMatcher($nonString);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     *
     * @dataProvider nonObjectProvider
     */
    public function has_institution_matcher_only_matches_objects_against_a_given_institution(
        bool|int|float|string|array|null $nonObject,
    ): void {
        $institution = 'surfnet.nl';

        $hasInstitutionMatcher = new HasInstitutionMatcher($institution);
        $match = $hasInstitutionMatcher->match($nonObject);

        $this->assertFalse($match);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     */
    public function has_institution_matcher_does_not_match_when_object_has_no_institution_property_and_no_institution_accessor(): void
    {
        $institution = 'surfnet.nl';

        $nonMatchingObject = new ObjectWithoutInstitutionPropertyAndAccessor;

        $hasInstitutionMatcher = new HasInstitutionMatcher($institution);
        $match = $hasInstitutionMatcher->match($nonMatchingObject);

        $this->assertFalse($match);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     */
    public function has_institution_matcher_does_not_match_when_objects_accessed_institution_differs_from_given_institution(): void
    {
        $institution = 'surfnet.nl';
        $differentInstitution = 'not-surfnet.nl';

        $nonMatchingObject = new ObjectWithInstitutionAccessor($institution);

        $hasInstitutionMatcher = new HasInstitutionMatcher($differentInstitution);
        $match = $hasInstitutionMatcher->match($nonMatchingObject);

        $this->assertFalse($match);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     */
    public function has_institution_matcher_matches_when_objects_accessed_institution_is_the_same_as_given_institution(): void
    {
        $institution = 'surfnet.nl';

        $nonMatchingObject = new ObjectWithInstitutionAccessor($institution);

        $hasInstitutionMatcher = new HasInstitutionMatcher($institution);
        $match = $hasInstitutionMatcher->match($nonMatchingObject);

        $this->assertTrue($match);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     */
    public function has_institution_matcher_does_not_match_when_objects_institution_property_differs_from_given_institution(): void
    {
        $institution = 'surfnet.nl';
        $differentInstitution = 'not-surfnet.nl';

        $nonMatchingObject = new ObjectWithInstitutionProperty($institution);

        $hasInstitutionMatcher = new HasInstitutionMatcher($differentInstitution);
        $match = $hasInstitutionMatcher->match($nonMatchingObject);

        $this->assertFalse($match);
    }

    /**
     * @test
     * @group mockery
     * @group institution
     */
    public function has_institution_matcher_matches_when_objects_institution_property_is_the_same_as_given_institution(): void
    {
        $institution = 'surfnet.nl';

        $nonMatchingObject = new ObjectWithInstitutionProperty($institution);

        $hasInstitutionMatcher = new HasInstitutionMatcher($institution);
        $match = $hasInstitutionMatcher->match($nonMatchingObject);

        $this->assertTrue($match);
    }

    public function nonStringProvider(): array
    {
        return [
            'null' => [null],
            'array' => [[]],
            'boolean' => [true],
            'integer' => [1],
            'float' => [1.2],
            'object' => [new stdClass()],
        ];
    }

    public function nonObjectProvider(): array
    {
        return [
            'null' => [null],
            'array' => [[]],
            'boolean' => [true],
            'integer' => [1],
            'float' => [1.2],
            'string' => ['string'],
        ];
    }
}

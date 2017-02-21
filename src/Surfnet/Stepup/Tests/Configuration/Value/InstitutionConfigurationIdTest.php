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

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;

class InstitutionConfigurationIdTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function two_institution_configuration_ids_created_for_the_different_institution_are_not_equal()
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $different = InstitutionConfigurationId::from(new Institution('A different institution'));

        $this->assertNotEquals($institutionConfigurationId, $different);
    }

    /**
     * @test
     * @group domain
     *
     * @dataProvider nonStringOrEmptyStringProvider
     * @param $nonStringOrEmptyString
     */
    public function an_institution_configuration_id_cannot_be_created_from_something_other_than_a_string($nonStringOrEmptyString)
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\InvalidArgumentException');

        new InstitutionConfigurationId($nonStringOrEmptyString);
    }

    /**
     * @test
     * @group domain
     */
    public function an_institution_configuration_id_cannot_be_created_from_something_other_than_a_uuid()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\InvalidArgumentException');

        $nonUuid = 'this-is-not-a-uuid';

        new InstitutionConfigurationId($nonUuid);
    }

    /**
     * @test
     * @group domain
     */
    public function two_institution_configuration_ids_created_for_the_same_institution_are_equal()
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $same = InstitutionConfigurationId::from(new Institution('An institution'));

        $this->assertEquals($institutionConfigurationId, $same);
    }

    /**
     * @test
     * @group domain
     */
    public function institution_configuration_ids_are_created_case_insensitively_from_institutions()
    {
        $mixedCaseInstitution = new Institution('An InStItUtIoN');
        $lowerCaseInstitution = new Institution('an institution');

        $mixedCaseInstitutionConfigurationId = InstitutionConfigurationId::normalizedFrom($mixedCaseInstitution);
        $lowerCaseInstitutionConfigurationId = InstitutionConfigurationId::normalizedFrom($lowerCaseInstitution);

        $isSameId = $mixedCaseInstitutionConfigurationId->equals($lowerCaseInstitutionConfigurationId);

        $this->assertTrue(
            $isSameId,
            'An InstitutionConfigurationId based on an institution with mixed casing'
            . 'should match an InstitutionConfigurationId based on the same institution in lower case'
        );
    }

    /**
     * @test
     * @group domain
     */
    public function normalized_institution_configuration_ids_and_unnormalized_institution_configuration_ids_are_not_the_same()
    {
        $mixedCaseInstitution = new Institution('An InStItUtIoN');

        $unnormalizedInstitutionConfigurationId = InstitutionConfigurationId::from($mixedCaseInstitution);
        $normalizedInstitutionConfigurationId   = InstitutionConfigurationId::normalizedFrom($mixedCaseInstitution);

        $isSameId = $unnormalizedInstitutionConfigurationId->equals($normalizedInstitutionConfigurationId);

        $this->assertFalse(
            $isSameId,
            'An normalized InstitutionConfigurationId based on an institution with mixed casing'
            . 'should not match an unnormalized InstitutionConfigurationId based on the same institution'
        );
    }

    /**
     * dataprovider
     */
    public function nonStringOrEmptyStringProvider()
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}

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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class InstitutionConfigurationIdTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[Group('domain')]
    public function two_institution_configuration_ids_created_for_the_different_institution_are_not_equal(): void
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $different = InstitutionConfigurationId::from(new Institution('A different institution'));

        $this->assertNotEquals($institutionConfigurationId, $different);
    }

    #[Test]
    #[DataProvider('nonStringOrEmptyStringProvider')]
    #[Group('domain')]
    public function an_institution_configuration_id_cannot_be_created_from_something_other_than_a_string(
        string $nonStringOrEmptyString,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new InstitutionConfigurationId($nonStringOrEmptyString);
    }

    #[Test]
    #[Group('domain')]
    public function an_institution_configuration_id_cannot_be_created_from_something_other_than_a_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $nonUuid = 'this-is-not-a-uuid';

        new InstitutionConfigurationId($nonUuid);
    }

    #[Test]
    #[Group('domain')]
    public function two_institution_configuration_ids_created_for_the_same_institution_are_equal(): void
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $same = InstitutionConfigurationId::from(new Institution('An institution'));

        $this->assertEquals($institutionConfigurationId, $same);
    }

    #[Test]
    #[Group('domain')]
    public function institution_configuration_ids_are_created_case_insensitively_from_institutions(): void
    {
        $mixedCaseInstitution = new Institution('An InStItUtIoN');
        $lowerCaseInstitution = new Institution('an institution');

        $mixedCaseInstitutionConfigurationId = InstitutionConfigurationId::normalizedFrom($mixedCaseInstitution);
        $lowerCaseInstitutionConfigurationId = InstitutionConfigurationId::normalizedFrom($lowerCaseInstitution);

        $isSameId = $mixedCaseInstitutionConfigurationId->equals($lowerCaseInstitutionConfigurationId);

        $this->assertTrue(
            $isSameId,
            'An InstitutionConfigurationId based on an institution with mixed casing'
            . 'should match an InstitutionConfigurationId based on the same institution in lower case',
        );
    }

    #[Test]
    #[Group('domain')]
    public function normalized_institution_configuration_ids_and_unnormalized_institution_configuration_ids_are_the_same(): void
    {
        $mixedCaseInstitution = new Institution('An InStItUtIoN');

        $unnormalizedInstitutionConfigurationId = InstitutionConfigurationId::from($mixedCaseInstitution);
        $normalizedInstitutionConfigurationId = InstitutionConfigurationId::normalizedFrom($mixedCaseInstitution);

        $isSameId = $unnormalizedInstitutionConfigurationId->equals($normalizedInstitutionConfigurationId);

        $this->assertTrue($isSameId);
    }

    /**
     * dataprovider
     */
    public static function nonStringOrEmptyStringProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
        ];
    }
}

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
     */
    public function two_institution_configuration_ids_created_for_the_same_institution_are_equal()
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $same = InstitutionConfigurationId::from(new Institution('An institution'));

        $this->assertEquals($institutionConfigurationId, $same);
    }
}

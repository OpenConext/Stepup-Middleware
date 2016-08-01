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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\Entity;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;

class InstitutionConfigurationOptionsTest extends TestCase
{
    /**
     * @test
     * @group entity
     */
    public function serialized_institution_configuration_options_have_the_correct_keys()
    {
        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            new Institution('An institution'),
            new UseRaLocationsOption(true),
            new ShowRaaContactInformationOption(true)
        );

        $serialized   = json_encode($institutionConfigurationOptions);
        $deserialized = json_decode($serialized, true);

        $this->assertArrayHasKey('institution', $deserialized);
        $this->assertArrayHasKey('use_ra_locations', $deserialized);
        $this->assertArrayHasKey('show_raa_contact_information', $deserialized);
    }

    /**
     * @test
     * @group entity
     */
    public function serialized_institution_configuration_options_have_the_correct_values()
    {
        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            new Institution('An institution'),
            new UseRaLocationsOption(true),
            new ShowRaaContactInformationOption(true)
        );

        $serialized   = json_encode($institutionConfigurationOptions);
        $deserialized = json_decode($serialized, true);

        $this->assertSame(
            $institutionConfigurationOptions->institution->getInstitution(),
            $deserialized['institution']
        );
        $this->assertSame(
            $institutionConfigurationOptions->useRaLocationsOption->isEnabled(),
            $deserialized['use_ra_locations']
        );
        $this->assertSame(
            $institutionConfigurationOptions->showRaaContactInformationOption->isEnabled(),
            $deserialized['show_raa_contact_information']
        );
    }
}

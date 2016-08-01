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
    public function institution_configuration_options_are_correctly_serialized_to_json()
    {
        $deserializedInstitutionConfigurationOptions = [
            'institution'                          => 'surfnet.nl',
            'use_ra_locations'                     => true,
            'show_raa_contact_information' => true,
        ];

        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            new Institution($deserializedInstitutionConfigurationOptions['institution']),
            new UseRaLocationsOption($deserializedInstitutionConfigurationOptions['use_ra_locations']),
            new ShowRaaContactInformationOption(
                $deserializedInstitutionConfigurationOptions['show_raa_contact_information']
            )
        );

        $expectedSerializedInstitutionConfigurationOptions = json_encode($deserializedInstitutionConfigurationOptions);
        $actualSerializedInstitutionConfigurationOptions   = json_encode($institutionConfigurationOptions);

        $this->assertSame(
            $expectedSerializedInstitutionConfigurationOptions,
            $actualSerializedInstitutionConfigurationOptions
        );
    }
}

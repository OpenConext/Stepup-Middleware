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

namespace Surfnet\Stepup\Tests\Configuration;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;

class InstitutionConfigurationTest extends AggregateRootScenarioTestCase
{
    /**
     * @test
     * @group aggregate
     */
    public function use_ra_locations_option_is_set_to_false_by_default_upon_creation_of_an_institution_configuration()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::from($institution);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(false);

        $expectedUseRaLocationsOption = new UseRaLocationsOption(false);

        $this->scenario
            ->when(function () use ($institution, $institutionConfigurationId, $showRaaContactInformationOption) {
                return InstitutionConfiguration::create(
                    $institutionConfigurationId,
                    $institution,
                    null,
                    $showRaaContactInformationOption
                );
            })->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $expectedUseRaLocationsOption,
                    $showRaaContactInformationOption
                )
            ]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function show_raa_contact_information_option_is_set_to_true_by_default_upon_creation_of_an_institution_configuration()
    {
        $institution                = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption       = new UseRaLocationsOption(true);

        $expectedShowRaaContactInformationOption = new ShowRaaContactInformationOption(true);

        $this->scenario
            ->when(function () use ($institution, $institutionConfigurationId, $useRaLocationsOption) {
                return InstitutionConfiguration::create(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption
                );
            })->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $expectedShowRaaContactInformationOption
                )
            ]);
    }

    protected function getAggregateRootClass()
    {
        return InstitutionConfiguration::class;
    }
}

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
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\SelectRaaOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaaOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\InstitutionOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;

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
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $expectedUseRaLocationsOption = new UseRaLocationsOption(false);
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->when(function () use ($institution, $institutionConfigurationId, $showRaaContactInformationOption, $verifyEmailOption) {
                return InstitutionConfiguration::create(
                    $institutionConfigurationId,
                    $institution
                );
            })->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $expectedUseRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    AllowedSecondFactorList::blank()
                ),
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
        $useRaLocationsOption       = new UseRaLocationsOption(false);
        $verifyEmailOption          = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $expectedShowRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->when(function () use ($institution, $institutionConfigurationId, $useRaLocationsOption, $verifyEmailOption) {
                return InstitutionConfiguration::create(
                    $institutionConfigurationId,
                    $institution
                );
            })->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $expectedShowRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    AllowedSecondFactorList::blank()
                ),
            ]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function use_ra_locations_option_is_not_changed_if_its_given_value_is_not_different_from_the_current_value()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::from($institution);
        $originalUseRaLocationsOption    = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $theSameUseRaLocationsOption = $originalUseRaLocationsOption;
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->withAggregateId((string) $institutionConfigurationId->getInstitutionConfigurationId())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalUseRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                )
            ])
            ->when(function (InstitutionConfiguration $institutionConfiguration) use ($theSameUseRaLocationsOption) {
                $institutionConfiguration->configureUseRaLocationsOption($theSameUseRaLocationsOption);
            })
            ->then([]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function show_raa_contact_information_option_is_not_changed_if_its_given_value_is_not_different_from_the_current_value()
    {
        $institution                             = new Institution('Institution');
        $institutionConfigurationId              = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption                    = new UseRaLocationsOption(true);
        $originalShowRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption                       = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $sameShowRaaContactInformationOption = $originalShowRaaContactInformationOption;
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->withAggregateId((string) $institutionConfigurationId->getInstitutionConfigurationId())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $originalShowRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                )
            ])
            ->when(function (InstitutionConfiguration $institutionConfiguration) use ($sameShowRaaContactInformationOption) {
                $institutionConfiguration->configureShowRaaContactInformationOption($sameShowRaaContactInformationOption);
            })
            ->then([]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function use_ra_locations_option_is_changed_if_its_given_value_is_different_from_the_current_value()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::from($institution);
        $originalUseRaLocationsOption    = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $expectedUseRaLocationsOption = new UseRaLocationsOption(false);
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->withAggregateId((string) $institutionConfigurationId->getInstitutionConfigurationId())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalUseRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                )
            ])
            ->when(function (InstitutionConfiguration $institutionConfiguration) use ($expectedUseRaLocationsOption) {
                $institutionConfiguration->configureUseRaLocationsOption($expectedUseRaLocationsOption);
            })
            ->then([
                new UseRaLocationsOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $expectedUseRaLocationsOption
                ),
            ]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function show_raa_contact_information_option_is_changed_if_its_given_value_is_different_from_the_current_value()
    {
        $institution                             = new Institution('Institution');
        $institutionConfigurationId              = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption                    = new UseRaLocationsOption(true);
        $originalShowRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption                       = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $expectedShowRaaContactInformationOption = new ShowRaaContactInformationOption(false);
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->withAggregateId((string) $institutionConfigurationId->getInstitutionConfigurationId())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $originalShowRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                )
            ])
            ->when(function (InstitutionConfiguration $institutionConfiguration) use ($expectedShowRaaContactInformationOption) {
                $institutionConfiguration->configureShowRaaContactInformationOption($expectedShowRaaContactInformationOption);
            })
            ->then([
                new ShowRaaContactInformationOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $expectedShowRaaContactInformationOption
                ),
            ]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function the_order_of_institutions_in_fga_option_do_not_manipulate_aggregate_state()
    {
        $institution                             = new Institution('Institution');
        $institutionConfigurationId              = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption                    = new UseRaLocationsOption(true);
        $originalShowRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption                       = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        // Configuration might change when it comes to ordering, this should not affect the aggregate state as the value
        // did not change, the order did.
        $useRaOption = InstitutionOption::fromInstitutionConfig(InstitutionRole::useRa(), $institution, ['institution-a', 'institution-b']);
        $useRaOptionRevision = InstitutionOption::fromInstitutionConfig(InstitutionRole::useRa(), $institution, ['institution-b', 'institution-a']);

        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $this->scenario
            ->withAggregateId((string) $institutionConfigurationId->getInstitutionConfigurationId())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $originalShowRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $useRaOption,
                    $useRaaOption,
                    $selectRaaOption
                )
            ])
            ->when(function (InstitutionConfiguration $institutionConfiguration) use ($useRaOptionRevision) {
                $institutionConfiguration->configureUseRaOption($useRaOptionRevision);
            })
            ->then([]);
    }

    /**
     * @test
     * @group aggregate
     */
    public function test_the_setting_of_fga_options_on_an_institution_configuration()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::from($institution);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $expectedUseRaLocationsOption = new UseRaLocationsOption(false);
        $useRaOption = InstitutionOption::getDefault(InstitutionRole::useRa(), $institution);
        $useRaaOption = InstitutionOption::getDefault(InstitutionRole::useRaa(), $institution);
        $selectRaaOption = InstitutionOption::getDefault(InstitutionRole::selectRaa(), $institution);

        $updatedRaOption = InstitutionOption::fromInstitutionConfig(InstitutionRole::useRa(), $institution, ['Institution']);
        $updatedRaaOption = InstitutionOption::fromInstitutionConfig(InstitutionRole::useRaa(), $institution, []);
        $updatedSelectRaaOption = InstitutionOption::fromInstitutionConfig(InstitutionRole::selectRaa(), $institution, ['Institution', 'Institution2']);

        $this->scenario
            ->when(
                function () use (
                    $institution,
                    $institutionConfigurationId,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $updatedRaOption,
                    $updatedRaaOption,
                    $updatedSelectRaaOption
                ) {
                    $institutionConfiguration = InstitutionConfiguration::create(
                        $institutionConfigurationId,
                        $institution
                    );

                    // First destroy the current config
                    $institutionConfiguration->destroy();

                    // Then set the new options
                    $institutionConfiguration->configureUseRaOption($updatedRaOption);
                    $institutionConfiguration->configureUseRaaOption($updatedRaaOption);
                    $institutionConfiguration->configureSelectRaaOption($updatedSelectRaaOption);

                    return $institutionConfiguration;
                }
            )->then(
                [
                    new NewInstitutionConfigurationCreatedEvent(
                        $institutionConfigurationId,
                        $institution,
                        $expectedUseRaLocationsOption,
                        $showRaaContactInformationOption,
                        $verifyEmailOption,
                        $numberOfTokensPerIdentityOption,
                        $useRaOption,
                        $useRaaOption,
                        $selectRaaOption
                    ),
                    new AllowedSecondFactorListUpdatedEvent(
                        $institutionConfigurationId,
                        $institution,
                        AllowedSecondFactorList::blank()
                    ),
                    new InstitutionConfigurationRemovedEvent(
                        $institutionConfigurationId,
                        $institution
                    ),
                    new UseRaaOptionChangedEvent(
                        $institutionConfigurationId,
                        $institution,
                        $updatedRaaOption
                    ),
                    new SelectRaaOptionChangedEvent(
                        $institutionConfigurationId,
                        $institution,
                        $updatedSelectRaaOption
                    ),
                ]
            );
    }

    protected function getAggregateRootClass()
    {
        return InstitutionConfiguration::class;
    }
}

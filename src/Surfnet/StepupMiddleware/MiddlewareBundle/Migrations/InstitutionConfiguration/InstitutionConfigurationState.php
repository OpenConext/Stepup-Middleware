<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Migrations\InstitutionConfiguration;

use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\RuntimeException;

final class InstitutionConfigurationState
{
    /**
     * @var MappedInstitutionConfiguration[]
     */
    private $mappedInstitutionConfigurations;

    /**
     * @param ConfiguredInstitution[]           $configuredInstitutions
     * @param InstitutionConfigurationOptions[] $institutionConfigurationOptions
     * @param RaLocation[]                      $raLocations
     * @return InstitutionConfigurationState
     */
    public static function load(
        $configuredInstitutions,
        $institutionConfigurationOptions,
        $raLocations
    ) {
        $optionInstitutions = array_map(function (InstitutionConfigurationOptions $options) {
            return $options->institution->getInstitution();
        }, $institutionConfigurationOptions);
        $mappedConfigurationOptions = array_combine($optionInstitutions, $institutionConfigurationOptions);

        $mappedRaLocations = [];
        foreach ($raLocations as $raLocation) {
            $institution = $raLocation->institution->getInstitution();
            $mappedRaLocations[$institution][] = $raLocation;
        }

        $mappedInstitutionConfigurations = [];
        foreach ($configuredInstitutions as $institution) {
            $institutionName = $institution->institution->getInstitution();
            if (!array_key_exists($institutionName, $mappedConfigurationOptions)) {
                throw new RuntimeException(sprintf(
                    'Institution "%s" has been configured, but does not have options.',
                    $institutionName
                ));
            }

            /** @var InstitutionConfigurationOptions $options */
            $options = $mappedConfigurationOptions[$institutionName];
            $locations = isset($mappedRaLocations[$institutionName]) ? $mappedRaLocations[$institutionName] : [];

            $mappedInstitutionConfigurations[] = new MappedInstitutionConfiguration(
                $institution->institution,
                $options->useRaLocationsOption,
                $options->showRaaContactInformationOption,
                $options->verifyEmailOption,
                $options->selfVetOption,
                $options->numberOfTokensPerIdentityOption,
                $locations
            );
        }

        return new self($mappedInstitutionConfigurations);
    }

    /**
     * @param MappedInstitutionConfiguration[] $mappedInstitutionConfigurations
     */
    private function __construct(array $mappedInstitutionConfigurations)
    {
        $this->mappedInstitutionConfigurations = $mappedInstitutionConfigurations;
    }

    /**
     * @return \Generator
     */
    public function inferRemovalCommands()
    {
        foreach ($this->mappedInstitutionConfigurations as $mappedInstitutionConfiguration) {
            yield $mappedInstitutionConfiguration->inferRemoveInstitutionConfigurationByIdCommand();
        }
    }

    /**
     * @return \Generator
     */
    public function inferCreateCommands()
    {
        foreach ($this->mappedInstitutionConfigurations as $mappedInstitutionConfiguration) {
            yield $mappedInstitutionConfiguration->inferCreateInstitutionConfigurationCommand();
        }
    }

    /**
     * @return \Generator
     */
    public function inferReconfigureCommands()
    {
        foreach ($this->mappedInstitutionConfigurations as $mappedInstitutionConfiguration) {
            yield $mappedInstitutionConfiguration->inferReconfigureInstitutionConfigurationCommand();
        }
    }

    /**
     * @return \Generator
     */
    public function inferAddRaLocationCommands()
    {
        foreach ($this->mappedInstitutionConfigurations as $mappedInstitutionConfiguration) {
            foreach ($mappedInstitutionConfiguration->inferAddRaLocationCommands() as $command) {
                yield $command;
            }
        }
    }
}

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

use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;

final readonly class MappedInstitutionConfiguration
{
    public function __construct(
        private Institution $institution,
        private UseRaLocationsOption $useRaLocationsOption,
        private ShowRaaContactInformationOption $showRaaContactInformationOption,
        private VerifyEmailOption $verifyEmailOption,
        private SelfVetOption $selfVetOption,
        private NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption,
        private array $raLocations,
    ) {
    }

    public function inferRemoveInstitutionConfigurationByIdCommand(): RemoveInstitutionConfigurationByUnnormalizedIdCommand
    {
        $command = new RemoveInstitutionConfigurationByUnnormalizedIdCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->institution = $this->institution->getInstitution();

        return $command;
    }

    public function inferCreateInstitutionConfigurationCommand(): CreateInstitutionConfigurationCommand
    {
        $command = new CreateInstitutionConfigurationCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->institution = $this->institution->getInstitution();

        return $command;
    }

    public function inferReconfigureInstitutionConfigurationCommand(): ReconfigureInstitutionConfigurationOptionsCommand
    {
        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->institution = $this->institution->getInstitution();
        $command->useRaLocationsOption = $this->useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $this->showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $this->verifyEmailOption->isEnabled();
        $command->selfVetOption = $this->selfVetOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $this->numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity(
        );

        return $command;
    }

    /**
     * @return AddRaLocationCommand[]
     */
    public function inferAddRaLocationCommands(): array
    {
        $commands = [];
        $institution = $this->institution->getInstitution();

        foreach ($this->raLocations as $raLocation) {
            $command = new AddRaLocationCommand();
            $command->UUID = (string)Uuid::uuid4();
            $command->institution = $institution;
            $command->raLocationId = $raLocation->id;
            $command->raLocationName = $raLocation->name->getRaLocationName();
            $command->contactInformation = $raLocation->contactInformation->getContactInformation();
            $command->location = $raLocation->location->getLocation();

            $commands[] = $command;
        }

        return $commands;
    }
}

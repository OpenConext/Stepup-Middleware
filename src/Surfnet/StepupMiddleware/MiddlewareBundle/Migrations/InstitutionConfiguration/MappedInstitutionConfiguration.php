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

use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;

final class MappedInstitutionConfiguration
{
    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var ShowRaaContactInformationOption
     */
    private $showRaaContactInformationOption;

    /**
     * @var UseRaLocationsOption
     */
    private $useRaLocationsOption;

    /**
     * @var VerifyEmailOption
     */
    private $verifyEmailOption;

    /** @var SelfVetOption */
    private $selfVetOption;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    private $numberOfTokensPerIdentityOption;

    /**
     * @var RaLocation[]
     */
    private $raLocations;

    /**
     * @param Institution $institution
     * @param UseRaLocationsOption $useRaLocationsOption
     * @param ShowRaaContactInformationOption $showRaaContactInformationOption
     * @param VerifyEmailOption $verifyEmailOption
     * @param NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption
     * @param RaLocation[] $raLocations
     */
    public function __construct(
        Institution $institution,
        UseRaLocationsOption $useRaLocationsOption,
        ShowRaaContactInformationOption $showRaaContactInformationOption,
        VerifyEmailOption $verifyEmailOption,
        SelfVetOption $selfVetOption,
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption,
        array $raLocations
    ) {
        $this->institution                     = $institution;
        $this->useRaLocationsOption            = $useRaLocationsOption;
        $this->showRaaContactInformationOption = $showRaaContactInformationOption;
        $this->verifyEmailOption               = $verifyEmailOption;
        $this->selfVetOption = $selfVetOption;
        $this->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption;
        $this->raLocations                     = $raLocations;
    }

    /**
     * @return RemoveInstitutionConfigurationByUnnormalizedIdCommand
     */
    public function inferRemoveInstitutionConfigurationByIdCommand()
    {
        $command              = new RemoveInstitutionConfigurationByUnnormalizedIdCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $this->institution->getInstitution();

        return $command;
    }

    /**
     * @return CreateInstitutionConfigurationCommand
     */
    public function inferCreateInstitutionConfigurationCommand()
    {
        $command              = new CreateInstitutionConfigurationCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $this->institution->getInstitution();

        return $command;
    }

    /**
     * @return ReconfigureInstitutionConfigurationOptionsCommand
     */
    public function inferReconfigureInstitutionConfigurationCommand()
    {
        $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->UUID                            = (string) Uuid::uuid4();
        $command->institution                     = $this->institution->getInstitution();
        $command->useRaLocationsOption            = $this->useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $this->showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption               = $this->verifyEmailOption->isEnabled();
        $command->selfVetOption= $this->selfVetOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $this->numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();

        return $command;
    }

    /**
     * @return AddRaLocationCommand[]
     */
    public function inferAddRaLocationCommands()
    {
        $commands = [];
        $institution = $this->institution->getInstitution();

        foreach ($this->raLocations as $raLocation) {
            $command                     = new AddRaLocationCommand();
            $command->UUID               = (string) Uuid::uuid4();
            $command->institution        = $institution;
            $command->raLocationId       = $raLocation->id ;
            $command->raLocationName     = $raLocation->name->getRaLocationName();
            $command->contactInformation = $raLocation->contactInformation->getContactInformation();
            $command->location           = $raLocation->location->getLocation();

            $commands[] = $command;
        }

        return $commands;
    }
}

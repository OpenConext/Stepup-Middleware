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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveRaLocationCommand;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Value objects
 */
class InstitutionConfigurationCommandHandler extends CommandHandler
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function handleCreateInstitutionConfigurationCommand(CreateInstitutionConfigurationCommand $command)
    {
        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);

        try {
            /** @var InstitutionConfiguration $institutionConfiguration */
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId()
            );

            $institutionConfiguration->rebuild();

        } catch (AggregateNotFoundException $exception) {
            $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        }

        $this->repository->save($institutionConfiguration);
    }

    public function handleReconfigureInstitutionConfigurationOptionsCommand(
        ReconfigureInstitutionConfigurationOptionsCommand $command
    ) {
        $institution = new Institution($command->institution);

        $allowedSecondFactors = array_map(function ($allowedSecondFactor) {
            return new SecondFactorType($allowedSecondFactor);
        }, $command->allowedSecondFactors);


        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->configureUseRaLocationsOption(
            new UseRaLocationsOption($command->useRaLocationsOption)
        );
        $institutionConfiguration->configureShowRaaContactInformationOption(
            new ShowRaaContactInformationOption($command->showRaaContactInformationOption)
        );
        $institutionConfiguration->updateAllowedSecondFactorList(
            AllowedSecondFactorList::ofTypes($allowedSecondFactors)
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleAddRaLocationCommand(AddRaLocationCommand $command)
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->addRaLocation(
            new RaLocationId($command->raLocationId),
            new RaLocationName($command->raLocationName),
            new Location($command->location),
            new ContactInformation($command->contactInformation)
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleChangeRaLocationCommand(ChangeRaLocationCommand $command)
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->changeRaLocation(
            new RaLocationId($command->raLocationId),
            new RaLocationName($command->raLocationName),
            new Location($command->location),
            new ContactInformation($command->contactInformation)
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleRemoveRaLocationCommand(RemoveRaLocationCommand $command)
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->removeRaLocation(new RaLocationId($command->raLocationId));

        $this->repository->save($institutionConfiguration);
    }

    public function handleRemoveInstitutionConfigurationByUnnormalizedIdCommand(
        RemoveInstitutionConfigurationByUnnormalizedIdCommand $command
    ) {
        $institution = new Institution($command->institution);

        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $institutionConfiguration = $this->repository->load(
            $institutionConfigurationId->getInstitutionConfigurationId()
        );
        $institutionConfiguration->destroy();

        $this->repository->save($institutionConfiguration);
    }

    /**
     * @deprecated Should be used until existing institution configurations have been migrated to using normalized ids
     *
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    private function loadInstitutionConfigurationFor(Institution $institution)
    {
        try {
            $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId()
            );
        } catch (AggregateNotFoundException $exception) {
            $institutionConfigurationId = InstitutionConfigurationId::from($institution);
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId()
            );
        }

        return $institutionConfiguration;
    }
}

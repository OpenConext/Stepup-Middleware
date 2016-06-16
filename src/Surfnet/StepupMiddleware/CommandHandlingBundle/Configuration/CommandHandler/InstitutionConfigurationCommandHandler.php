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
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
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

    public function handleAddRaLocationCommand(AddRaLocationCommand $command)
    {
        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $institutionConfiguration   = $this->getInstitutionConfiguration($institutionConfigurationId);

        if ($institutionConfiguration === null) {
            $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        }

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
        // Implement
    }

    public function handleRemoveRaLocationCommand(RemoveRaLocationCommand $command)
    {
        // Implement
    }

    private function getInstitutionConfiguration(InstitutionConfigurationId $institutionConfigurationId)
    {
        try {
            return $this->repository->load($institutionConfigurationId);
        } catch (AggregateNotFoundException $e) {
            return null;
        }
    }
}

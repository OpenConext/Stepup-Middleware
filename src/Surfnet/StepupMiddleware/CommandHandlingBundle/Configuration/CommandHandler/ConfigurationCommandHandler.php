<?php

/**
 * Copyright 2014 SURFnet bv
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
use Doctrine\DBAL\Driver\Connection;
use Surfnet\Stepup\Configuration\Configuration;
use Surfnet\Stepup\Configuration\EventSourcing\ConfigurationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\RaaProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\SraaProjector;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Surfnet\StepupMiddleware\GatewayBundle\Service\GatewayConfigurationService;

class ConfigurationCommandHandler extends CommandHandler
{
    /**
     * @var \Surfnet\Stepup\Configuration\EventSourcing\ConfigurationRepository
     */
    private $repository;

    /**
     * @var \Surfnet\StepupMiddleware\GatewayBundle\Service\GatewayConfigurationService
     */
    private $gatewayConfigurationService;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\RaaProjector
     */
    private $raaProjector;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\SraaProjector
     */
    private $sraaProjector;

    /**
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $middlewareConnection;

    /**
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $gatewayConnection;

    /**
     * @param ConfigurationRepository     $repository
     * @param GatewayConfigurationService $gatewayConfigurationService
     * @param RaaProjector                $raaProjector
     * @param SraaProjector               $sraaProjector
     * @param Connection                  $middlewareConnection
     * @param Connection                  $gatewayConnection
     */
    public function __construct(
        ConfigurationRepository $repository,
        GatewayConfigurationService $gatewayConfigurationService,
        RaaProjector $raaProjector,
        SraaProjector $sraaProjector,
        Connection $middlewareConnection,
        Connection $gatewayConnection
    ) {
        $this->repository = $repository;
        $this->gatewayConfigurationService = $gatewayConfigurationService;
        $this->raaProjector = $raaProjector;
        $this->sraaProjector = $sraaProjector;
        $this->middlewareConnection = $middlewareConnection;
        $this->gatewayConnection = $gatewayConnection;
    }

    public function handleUpdateConfigurationCommand(UpdateConfigurationCommand $command)
    {
        $configuration = $this->getConfiguration();
        if (!$configuration) {
            $configuration = Configuration::create();
        }

        $this->middlewareConnection->beginTransaction();
        $this->gatewayConnection->beginTransaction();

        try {
            $configuration->update($command->configuration);

            $event = $configuration->getLastUncommittedServiceProvidersUpdatedEvent();
            $this->gatewayConfigurationService->updateServiceProviders($event->serviceProviders);

            $event = $configuration->getLastUncommittedRaaUpdatedEvent();
            $this->raaProjector->updateRaaConfiguration($event);

            $event = $configuration->getLastUncommittedSraaUpdatedEvent();
            $this->sraaProjector->replaceSraaConfiguration($event);

            $this->repository->add($configuration);
        } catch (\Exception $e) {
            $this->middlewareConnection->rollBack();
            $this->gatewayConnection->rollBack();

            throw $e;
        }

        $this->middlewareConnection->commit();
        $this->gatewayConnection->commit();
    }

    /**
     * @return null|\Surfnet\Stepup\Configuration\Api\Configuration
     */
    private function getConfiguration()
    {
        try {
            return $this->repository->load(Configuration::CONFIGURATION_ID);
        } catch (AggregateNotFoundException $e) {
            return null;
        }
    }
}

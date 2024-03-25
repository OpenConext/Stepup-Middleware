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

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\Repository\AggregateNotFoundException;
use Surfnet\Stepup\Configuration\Configuration;
use Surfnet\Stepup\Configuration\EventSourcing\ConfigurationRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;

class ConfigurationCommandHandler extends SimpleCommandHandler
{
    public function __construct(
        private readonly ConfigurationRepository $repository,
    ) {
    }

    public function handleUpdateConfigurationCommand(UpdateConfigurationCommand $command): void
    {
        $configuration = $this->getConfiguration();
        if (!$configuration instanceof \Surfnet\Stepup\Configuration\Configuration) {
            $configuration = Configuration::create();
        }

        $configuration->update($command->configuration);

        $this->repository->save($configuration);
    }

    private function getConfiguration(): ?Configuration
    {
        try {
            /** @var Configuration $configuration */
            $configuration = $this->repository->load(Configuration::CONFIGURATION_ID);
            return $configuration;
        } catch (AggregateNotFoundException) {
            return null;
        }
    }
}

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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener;

use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

final class WhitelistEventListener extends EventListener
{
    /**
     * @var ConfiguredInstitutionRepository
     */
    private $configuredInstitutionRepository;

    /**
     * @var Pipeline
     */
    private $pipeline;

    public function __construct(ConfiguredInstitutionRepository $configuredInstitutionRepository, Pipeline $pipeline)
    {
        $this->configuredInstitutionRepository = $configuredInstitutionRepository;
        $this->pipeline                        = $pipeline;
    }

    public function applyWhitelistCreatedEvent(WhitelistCreatedEvent $event)
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createInstitutionConfigurationFor($institution);
        }
    }

    public function applyWhitelistReplacedEvent(WhitelistReplacedEvent $event)
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createInstitutionConfigurationFor($institution);
        }
    }

    public function applyInstitutionsAddedToWhitelistEvent(InstitutionsAddedToWhitelistEvent $event)
    {
        foreach ($event->addedInstitutions as $addedInstitution) {
            $institution = new Institution($addedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createInstitutionConfigurationFor($institution);
        }
    }

    /**
     * @param Institution $institution
     */
    private function createInstitutionConfigurationFor(Institution $institution)
    {
        $command              = new CreateInstitutionConfigurationCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $institution;

        $this->pipeline->process($command);
    }
}

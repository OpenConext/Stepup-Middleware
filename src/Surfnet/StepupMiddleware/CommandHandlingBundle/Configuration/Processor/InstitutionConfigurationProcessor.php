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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Processor;

use Broadway\Processor\Processor;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class InstitutionConfigurationProcessor extends Processor
{
    /**
     * The container needs to be called during runtime in order to prevent a circular reference
     * during container compilation.
     */
    public function __construct(
        private readonly ConfiguredInstitutionRepository $configuredInstitutionRepository,
        private readonly Pipeline $pipeline,
    ) {
    }

    public function handleIdentityCreatedEvent(IdentityCreatedEvent $event): void
    {
        $institution = new Institution($event->identityInstitution->getInstitution());

        if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
            return;
        }

        $this->createConfigurationFor($institution);
    }

    public function handleWhitelistCreatedEvent(WhitelistCreatedEvent $event): void
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    public function handleWhitelistReplacedEvent(WhitelistReplacedEvent $event): void
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    public function handleInstitutionsAddedToWhitelistEvent(InstitutionsAddedToWhitelistEvent $event): void
    {
        foreach ($event->addedInstitutions as $addedInstitution) {
            $institution = new Institution($addedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    private function createConfigurationFor(Institution $institution): void
    {
        $command = new CreateInstitutionConfigurationCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->institution = $institution->getInstitution();

        $this->pipeline->process($command);
    }
}

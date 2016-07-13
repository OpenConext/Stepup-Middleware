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

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

final class IdentityEventListener implements EventListenerInterface
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

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event  = $domainMessage->getPayload();

        $classParts = explode('\\', get_class($event));
        $method = 'apply' . end($classParts);

        if (!method_exists($this, $method)) {
            return;
        }

        $this->$method($event, $domainMessage);
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $institution = new Institution($event->identityInstitution->getInstitution());

        if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
            return;
        }

        $command              = new CreateInstitutionConfigurationCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $institution;

        $this->pipeline->process($command);
    }
}

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

namespace Surfnet\StepupMiddleware\GatewayBundle\Projector;

use Surfnet\Stepup\Configuration\Event\IdentityProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Projector\Projector;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SamlEntity;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SamlEntityRepository;

class SamlEntityProjector extends Projector
{
    public function __construct(
        private readonly SamlEntityRepository $samlEntityRepository,
    ) {
    }

    public function applyServiceProvidersUpdatedEvent(ServiceProvidersUpdatedEvent $event): void
    {
        $spConfigurations = [];
        foreach ($event->serviceProviders as $configuration) {
            $newConfiguration = $configuration;
            unset($newConfiguration['entity_id']);

            $spConfigurations[] = SamlEntity::createServiceProvider($configuration['entity_id'], $newConfiguration);
        }

        $this->samlEntityRepository->replaceAllSps($spConfigurations);
    }

    public function applyIdentityProvidersUpdatedEvent(IdentityProvidersUpdatedEvent $event): void
    {
        $spConfigurations = [];
        foreach ($event->identityProviders as $configuration) {
            $newConfiguration = $configuration;
            unset($newConfiguration['entity_id']);

            $spConfigurations[] = SamlEntity::createIdentityProvider($configuration['entity_id'], $newConfiguration);
        }

        $this->samlEntityRepository->replaceAllIdps($spConfigurations);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        // do nothing, no sensitive data in this projection
    }
}

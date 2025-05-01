<?php

declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
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

use Surfnet\Stepup\Configuration\Event\SsoRegistrationBypassOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\SsoRegistrationBypassOption;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Projector\Projector;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\SsoOn2faOptionChangedEvent;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\InstitutionConfiguration;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\InstitutionConfigurationRepository;

class InstitutionConfigurationProjector extends Projector
{
    public function __construct(private readonly InstitutionConfigurationRepository $repository)
    {
    }

    public function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event): void
    {
        $institutionConfiguration = new InstitutionConfiguration(
            (string)$event->institution,
            $event->ssoOn2faOption->isEnabled(),
            $event->ssoRegistrationBypassOption->isEnabled(),
        );

        $this->repository->save($institutionConfiguration);
    }

    public function applySsoOn2faOptionChangedEvent(SsoOn2faOptionChangedEvent $event): void
    {
        $institutionConfiguration = $this->repository->findByInstitution((string)$event->institution);
        if ($institutionConfiguration instanceof InstitutionConfiguration) {
            $institutionConfiguration->ssoOn2faEnabled = $event->ssoOn2faOption->isEnabled();
            $this->repository->save($institutionConfiguration);
            return;
        }
        // It can happen that the event changed for an institution that already exists, but is not yet projected to
        // this projection. In that case we can create it.
        $institutionConfiguration = new InstitutionConfiguration(
            (string)$event->institution,
            $event->ssoOn2faOption->isEnabled(),
            SsoRegistrationBypassOption::getDefault()->isEnabled(),
        );
        $this->repository->save($institutionConfiguration);
    }

    public function applySsoRegistrationBypassOptionChangedEvent(SsoRegistrationBypassOptionChangedEvent $event): void
    {
        $institutionConfiguration = $this->repository->findByInstitution((string)$event->institution);
        if ($institutionConfiguration instanceof InstitutionConfiguration) {
            $institutionConfiguration->ssoRegistrationBypass = $event->ssoRegistrationBypassOption->isEnabled();
            $this->repository->save($institutionConfiguration);
            return;
        }
        // It can happen that the event changed for an institution that already exists, but is not yet projected to
        // this projection. In that case we can create it.
        $institutionConfiguration = new InstitutionConfiguration(
            (string)$event->institution,
            SsoOn2faOption::getDefault()->isEnabled(),
            $event->ssoRegistrationBypassOption->isEnabled(),
        );
        $this->repository->save($institutionConfiguration);
    }

    public function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event): void
    {
        $this->repository->removeFor((string)$event->institution);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        // do nothing, no sensitive data in this projection
    }
}

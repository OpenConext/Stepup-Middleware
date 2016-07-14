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

use Surfnet\Stepup\Configuration\Api\InstitutionConfigurationCreationService;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;

final class IdentityEventListener extends EventListener
{
    /**
     * @var ConfiguredInstitutionRepository
     */
    private $configuredInstitutionRepository;

    /**
     * @var InstitutionConfigurationCreationService
     */
    private $institutionConfigurationCreationService;

    public function __construct(
        ConfiguredInstitutionRepository $configuredInstitutionRepository,
        InstitutionConfigurationCreationService $institutionConfigurationCreationService
    ) {
        $this->configuredInstitutionRepository = $configuredInstitutionRepository;
        $this->institutionConfigurationCreationService = $institutionConfigurationCreationService;
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $institution = new Institution($event->identityInstitution->getInstitution());

        if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
            return;
        }

        $this->institutionConfigurationCreationService->createConfigurationFor($institution);
    }
}

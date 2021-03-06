<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class InstitutionAuthorizationProjector extends Projector
{
    /**
     * @var InstitutionAuthorizationRepository
     */
    private $institutionAuthorizationRepository;
    /**
     * @var InstitutionConfigurationOptionsRepository
     */
    private $institutionConfigurationOptionsRepository;

    public function __construct(
        InstitutionAuthorizationRepository $institutionAuthorizationRepository,
        InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository
    ) {
        $this->institutionAuthorizationRepository = $institutionAuthorizationRepository;
        $this->institutionConfigurationOptionsRepository = $institutionConfigurationOptionsRepository;
    }

    public function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event)
    {
        $this->institutionAuthorizationRepository->setDefaultInstitutionOption($event->institution);
    }

    public function applyUseRaOptionChangedEvent(UseRaOptionChangedEvent $event)
    {
        $this->institutionAuthorizationRepository->saveInstitutionOption(
            $event->institution,
            $event->useRaOption
        );
    }

    public function applyUseRaaOptionChangedEvent(UseRaaOptionChangedEvent $event)
    {
        $this->institutionAuthorizationRepository->saveInstitutionOption(
            $event->institution,
            $event->useRaaOption
        );
    }

    public function applySelectRaaOptionChangedEvent(SelectRaaOptionChangedEvent $event)
    {
        $this->institutionAuthorizationRepository->saveInstitutionOption(
            $event->institution,
            $event->selectRaaOption
        );
    }

    public function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event)
    {
        $this->institutionAuthorizationRepository->clearInstitutionOption(
            $event->institution
        );
    }
}

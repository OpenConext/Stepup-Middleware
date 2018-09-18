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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\NumberOfTokensPerIdentityOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\AllowedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

final class InstitutionConfigurationOptionsProjector extends Projector
{
    /**
     * @var InstitutionConfigurationOptionsRepository
     */
    private $institutionConfigurationOptionsRepository;

    /**
     * @var AllowedSecondFactorRepository
     */
    private $allowedSecondFactorRepository;

    public function __construct(
        InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository,
        AllowedSecondFactorRepository $allowedSecondFactorRepository
    ) {
        $this->institutionConfigurationOptionsRepository = $institutionConfigurationOptionsRepository;
        $this->allowedSecondFactorRepository             = $allowedSecondFactorRepository;
    }

    public function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event)
    {
        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            $event->institution,
            $event->useRaLocationsOption,
            $event->showRaaContactInformationOption,
            $event->verifyEmailOption,
            $event->numberOfTokensPerIdentityOption,
            $event->useRaOption,
            $event->useRaaOption,
            $event->selectRaaOption
        );

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyUseRaLocationsOptionChangedEvent(UseRaLocationsOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->useRaLocationsOption = $event->useRaLocationsOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyShowRaaContactInformationOptionChangedEvent(ShowRaaContactInformationOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->showRaaContactInformationOption = $event->showRaaContactInformationOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyVerifyEmailOptionChangedEvent(VerifyEmailOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->verifyEmailOption = $event->verifyEmailOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyNumberOfTokensPerIdentityOptionChangedEvent(NumberOfTokensPerIdentityOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event)
    {
        $this->institutionConfigurationOptionsRepository->removeConfigurationOptionsFor($event->institution);
        $this->allowedSecondFactorRepository->clearAllowedSecondFactorListFor($event->institution);
    }

    public function applyUseRaOptionChangedEvent(UseRaOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->useRaOption = $event->useRaOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }
    public function applyUseRaaOptionChangedEvent(UseRaaOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->useRaaOption = $event->useRaaOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }
    public function applySelectRaaOptionChangedEvent(SelectRaaOptionChangedEvent $event)
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor($event->institution);
        $institutionConfigurationOptions->selectRaaOption = $event->selectRaaOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }
}

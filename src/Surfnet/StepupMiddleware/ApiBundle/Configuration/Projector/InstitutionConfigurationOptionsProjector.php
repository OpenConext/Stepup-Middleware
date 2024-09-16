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
use Surfnet\Stepup\Configuration\Event\SelfAssertedTokensOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SelfVetOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SsoOn2faOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\AllowedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

final class InstitutionConfigurationOptionsProjector extends Projector
{
    public function __construct(
        private readonly InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository,
        private readonly AllowedSecondFactorRepository $allowedSecondFactorRepository,
    ) {
    }

    public function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event): void
    {
        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            $event->institution,
            $event->useRaLocationsOption,
            $event->showRaaContactInformationOption,
            $event->verifyEmailOption,
            $event->numberOfTokensPerIdentityOption,
            $event->ssoOn2faOption,
            $event->selfVetOption,
            $event->selfAssertedTokensOption,
        );

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyUseRaLocationsOptionChangedEvent(UseRaLocationsOptionChangedEvent $event): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->useRaLocationsOption = $event->useRaLocationsOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyShowRaaContactInformationOptionChangedEvent(ShowRaaContactInformationOptionChangedEvent $event,): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->showRaaContactInformationOption = $event->showRaaContactInformationOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyVerifyEmailOptionChangedEvent(VerifyEmailOptionChangedEvent $event): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->verifyEmailOption = $event->verifyEmailOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyNumberOfTokensPerIdentityOptionChangedEvent(NumberOfTokensPerIdentityOptionChangedEvent $event,): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applySelfVetOptionChangedEvent(SelfVetOptionChangedEvent $event): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->selfVetOption = $event->selfVetOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applySsoOn2faOptionChangedEvent(SsoOn2faOptionChangedEvent $event): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->ssoOn2faOption = $event->ssoOn2faOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applySelfAssertedTokensOptionChangedEvent(SelfAssertedTokensOptionChangedEvent $event): void
    {
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsRepository->findConfigurationOptionsFor(
            $event->institution,
        );
        $institutionConfigurationOptions->selfAssertedTokensOption = $event->selfAssertedTokensOption;

        $this->institutionConfigurationOptionsRepository->save($institutionConfigurationOptions);
    }

    public function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event): void
    {
        $this->institutionConfigurationOptionsRepository->removeConfigurationOptionsFor($event->institution);
        $this->allowedSecondFactorRepository->clearAllowedSecondFactorListFor($event->institution);
    }
}

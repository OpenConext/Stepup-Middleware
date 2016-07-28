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
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

final class InstitutionConfigurationOptionsProjector extends Projector
{
    /**
     * @var InstitutionConfigurationOptionsRepository
     */
    private $repository;

    public function __construct(InstitutionConfigurationOptionsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event)
    {
        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            $event->institution,
            $event->useRaLocationsOption,
            $event->showRaaContactInformationOption
        );

        $this->repository->save($institutionConfigurationOptions);
    }

    public function applyUseRaLocationOptionChangedEvent(UseRaLocationsOptionChangedEvent $event)
    {
        $currentOptions = $this->repository->findConfigurationOptionsFor($event->institution);

        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            $event->institution,
            $event->useRaLocationsOption,
            new ShowRaaContactInformationOption($currentOptions->showRaaContactInformationOption)
        );

        $this->repository->save($institutionConfigurationOptions);
    }

    public function applyShowRaaContactInformationOptionChangedEvent(ShowRaaContactInformationOptionChangedEvent $event)
    {
        $currentOptions = $this->repository->findConfigurationOptionsFor($event->institution);

        $institutionConfigurationOptions = InstitutionConfigurationOptions::create(
            $event->institution,
            new UseRaLocationsOption($currentOptions->useRaLocationsOption),
            $event->showRaaContactInformationOption
        );

        $this->repository->save($institutionConfigurationOptions);
    }
}

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
use Surfnet\Stepup\Configuration\Event\InstitutionsWithRaLocationsUpdatedEvent;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionWithRaLocations;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionWithRaLocationsRepository;

final class InstitutionWithRaLocationsProjector extends Projector
{
    /**
     * @var InstitutionWithRaLocations
     */
    private $repository;

    public function __construct(InstitutionWithRaLocationsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applyInstitutionsWithRaLocationsUpdatedEvent(
        InstitutionsWithRaLocationsUpdatedEvent $event
    ) {
        foreach ($event->institutionsWithRaLocations as $institution) {
            $this->repository->addIfNotExists(InstitutionWithRaLocations::createFrom(new Institution($institution)));
        }
    }
}

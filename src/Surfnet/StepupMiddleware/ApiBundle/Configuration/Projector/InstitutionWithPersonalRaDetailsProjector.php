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
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Event\InstitutionsWithPersonalRaDetailsUpdatedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionWithPersonalRaDetails;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionWithPersonalRaDetailsRepository;

final class InstitutionWithPersonalRaDetailsProjector extends Projector
{
    /**
     * @var InstitutionWithPersonalRaDetails
     */
    private $repository;

    public function __construct(InstitutionWithPersonalRaDetailsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applyInstitutionsWithPersonalRaDetailsUpdatedEvent(
        InstitutionsWithPersonalRaDetailsUpdatedEvent $event
    ) {
        foreach ($event->institutionsWithPersonalRaDetails as $institution) {
            $this->repository->addIfNotExists(InstitutionWithPersonalRaDetails::create(Uuid::uuid4(), $institution));
        }
    }
}

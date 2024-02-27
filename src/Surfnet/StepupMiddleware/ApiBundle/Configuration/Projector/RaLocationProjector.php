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
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\RaLocationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;

class RaLocationProjector extends Projector
{
    private RaLocationRepository $repository;

    public function __construct(RaLocationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applyRaLocationAddedEvent(RaLocationAddedEvent $event): void
    {
        $raLocation = RaLocation::create(
            $event->raLocationId->getRaLocationId(),
            $event->institution,
            $event->raLocationName,
            $event->location,
            $event->contactInformation
        );

        $this->repository->save($raLocation);
    }

    public function applyRaLocationRenamedEvent(RaLocationRenamedEvent $event): void
    {
        $raLocation = $this->fetchRaLocationById($event->raLocationId);

        $raLocation->name = $event->raLocationName;

        $this->repository->save($raLocation);
    }

    public function applyRaLocationRelocatedEvent(RaLocationRelocatedEvent $event): void
    {
        $raLocation = $this->fetchRaLocationById($event->raLocationId);

        $raLocation->location = $event->location;

        $this->repository->save($raLocation);
    }

    public function applyRaLocationContactInformationChangedEvent(RaLocationContactInformationChangedEvent $event): void
    {
        $raLocation = $this->fetchRaLocationById($event->raLocationId);

        $raLocation->contactInformation = $event->contactInformation;

        $this->repository->save($raLocation);
    }

    public function applyRaLocationRemovedEvent(RaLocationRemovedEvent $event): void
    {
        $raLocation = $this->fetchRaLocationById($event->raLocationId);

        $this->repository->remove($raLocation);
    }

    public function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event): void
    {
        $this->repository->removeRaLocationsFor($event->institution);
    }

    /**
     * @param RaLocationId $raLocationId
     * @return RaLocation
     */
    private function fetchRaLocationById(RaLocationId $raLocationId)
    {
        $raLocation = $this->repository->findByRaLocationId($raLocationId);

        if (is_null($raLocation)) {
            throw new RuntimeException(
                'Tried to update an RA Locations contact information, but location could not be found'
            );
        }

        if (!$raLocation instanceof RaLocation) {
            throw new RuntimeException(
                'Tried to update an RA Locations contact information, but location is of the wrong type'
            );
        }

        return $raLocation;
    }
}

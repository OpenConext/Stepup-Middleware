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

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\SecondFactorRepository;

class SecondFactorProjector extends Projector
{
    /**
     * @var SecondFactorRepository
     */
    private $repository;

    /**
     * @param SecondFactorRepository $repository
     */
    public function __construct(SecondFactorRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $this->repository->save(
            new SecondFactor(
                (string) $event->identityId,
                (string) $event->nameId,
                (string) $event->institution,
                (string) $event->secondFactorId,
                $event->secondFactorIdentifier,
                $event->secondFactorType
            )
        );
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->repository->remove($this->repository->findOneBySecondFactorId($event->secondFactorId));
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->repository->remove($this->repository->findOneBySecondFactorId($event->secondFactorId));
    }
}

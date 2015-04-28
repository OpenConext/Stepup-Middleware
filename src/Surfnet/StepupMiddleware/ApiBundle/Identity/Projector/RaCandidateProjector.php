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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\IdentifyingData\Entity\IdentifyingDataRepository;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;

class RaCandidateProjector extends Projector
{
    /**
     * @var RaCandidateRepository
     */
    private $raCandidateRepository;

    /**
     * @var IdentifyingDataRepository
     */
    private $identifyingDataRepository;

    public function __construct(
        RaCandidateRepository $raCandidateRepository,
        IdentifyingDataRepository $identifyingDataRepository
    ) {
        $this->raCandidateRepository = $raCandidateRepository;
        $this->identifyingDataRepository = $identifyingDataRepository;
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $identifyingData = $this->identifyingDataRepository->getById($event->identifyingDataId);

        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $identifyingData->commonName,
            $identifyingData->email
        );

        $this->raCandidateRepository->save($candidate);
    }

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $identifyingData = $this->identifyingDataRepository->getById($event->identifyingDataId);

        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $identifyingData->commonName,
            $identifyingData->email
        );

        $this->raCandidateRepository->save($candidate);
    }

    public function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    public function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }
}

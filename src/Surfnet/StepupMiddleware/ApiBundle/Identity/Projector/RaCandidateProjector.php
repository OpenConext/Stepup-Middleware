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
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
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

    public function __construct(RaCandidateRepository $raCandidateRepository)
    {
        $this->raCandidateRepository = $raCandidateRepository;
    }

    /**
     * @param SecondFactorVettedEvent $event
     * @return void
     */
    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->commonName,
            $event->email
        );

        $this->raCandidateRepository->save($candidate);
    }

    /**
     * @param YubikeySecondFactorBootstrappedEvent $event
     * @return void
     */
    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->commonName,
            $event->email
        );

        $this->raCandidateRepository->save($candidate);
    }

    /**
     * @param VettedSecondFactorRevokedEvent $event
     * @return void
     */
    public function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param CompliedWithVettedSecondFactorRevocationEvent $event
     * @return void
     */
    public function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param SraaUpdatedEvent $event
     *
     * Removes all RaCandidates that have a nameId matching an SRAA, as they cannot be made RA(A) as they
     * already are SRAA.
     */
    public function applySraaUpdatedEvent(SraaUpdatedEvent $event)
    {
        $this->raCandidateRepository->removeByNameIds($event->sraaList);
    }

    /**
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param RegistrationAuthorityRetractedEvent $event
     * @return void
     */
    public function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->commonName,
            $event->email
        );

        $this->raCandidateRepository->save($candidate);
    }
}

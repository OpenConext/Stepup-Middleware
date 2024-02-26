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

use Broadway\Domain\DomainMessage;
use Broadway\ReadModel\Projector;
use DateTime as CoreDateTime;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\SecondFactorRevocation;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRevocationRepository;

class SecondFactorRevocationProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRevocationRepository
     */
    private $repository;

    public function __construct(SecondFactorRevocationRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function applyVettedSecondFactorRevokedEvent(
        VettedSecondFactorRevokedEvent $event,
        DomainMessage $domainMessage
    ) {
        $revocation = new SecondFactorRevocation();
        $revocation->id = (string) Uuid::uuid4();
        $revocation->institution = $event->identityInstitution;
        $revocation->secondFactorType = $event->secondFactorType->getSecondFactorType();
        $revocation->revokedBy = 'self';
        $revocation->recordedOn = new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString()));

        $this->repository->save($revocation);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event,
        DomainMessage $domainMessage
    ) {
        $revocation = new SecondFactorRevocation();
        $revocation->id = (string) Uuid::uuid4();
        $revocation->institution = $event->identityInstitution;
        $revocation->secondFactorType = $event->secondFactorType->getSecondFactorType();
        $revocation->revokedBy = 'ra';
        $revocation->recordedOn = new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString()));

        $this->repository->save($revocation);
    }
}

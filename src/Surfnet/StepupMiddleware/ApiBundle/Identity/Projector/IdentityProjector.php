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
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityProjector extends Projector
{
    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    /**
     * @var
     */
    private $identifyingDataRepository;

    public function __construct(
        IdentityRepository $identityRepository,
        IdentifyingDataRepository $identifyingDataRepository
    ) {
        $this->identityRepository = $identityRepository;
        $this->identifyingDataRepository = $identifyingDataRepository;
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $identifyingData = $this->identifyingDataRepository->getById($event->identifyingDataId);

        $this->identityRepository->save(Identity::create(
            (string) $event->identityId,
            $event->identityInstitution,
            (string) $event->nameId,
            $identifyingData->email,
            $identifyingData->commonName
        ));
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identifyingData = $this->identifyingDataRepository->getById($event->identifyingDataId);

        $identity->commonName = $identifyingData->commonName;

        $this->identityRepository->save($identity);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identifyingData = $this->identifyingDataRepository->getById($event->identifyingDataId);

        $identity->email = $identifyingData->email;

        $this->identityRepository->save($identity);
    }
}

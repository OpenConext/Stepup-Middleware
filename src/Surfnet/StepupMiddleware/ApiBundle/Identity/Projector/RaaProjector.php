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
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Raa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository;

class RaaProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository
     */
    private $raaRepository;

    public function __construct(RaaRepository $raaRepository)
    {
        $this->raaRepository = $raaRepository;
    }

    /**
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $raa = Raa::create($event->identityInstitution, $event->nameId, $event->location, $event->contactInformation);

        $this->raaRepository->save($raa);
    }

    public function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event
    ) {
        $raa = $this->raaRepository->findByNameId($event->nameId);

        if (!$raa) {
            return;
        }

        $raa->location = $event->location;
        $raa->contactInformation = $event->contactInformation;

        $this->raaRepository->save($raa);
    }

    public function applyAppointedAsRaEvent(AppointedAsRaEvent $event)
    {
        $raa = $this->raaRepository->findByNameId($event->nameId);

        $this->raaRepository->remove($raa);
    }
}

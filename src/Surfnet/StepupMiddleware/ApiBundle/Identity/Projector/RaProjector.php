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
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Ra;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaRepository;

class RaProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaRepository
     */
    private $raRepository;

    public function __construct(RaRepository $raRepository)
    {
        $this->raRepository = $raRepository;
    }

    /**
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $raa = Ra::create($event->identityInstitution, $event->nameId, $event->location, $event->contactInformation);

        $this->raRepository->save($raa);
    }
}

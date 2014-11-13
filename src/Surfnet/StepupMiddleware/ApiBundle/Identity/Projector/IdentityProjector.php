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
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\SecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityProjector extends Projector
{
    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(IdentityRepository $identityRepository)
    {
        $this->identityRepository = $identityRepository;
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->identityRepository->save(new Identity((string) $event->identityId, (string) $event->nameId));
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        if (!$identity instanceof Identity) {
            // @TODO Log?
            return;
        }

        $secondFactor = new SecondFactor(
            $identity,
            (string) $event->secondFactorId,
            'yubikey',
            (string) $event->yubikeyPublicId
        );
        $identity->addSecondFactor($secondFactor);

        $this->identityRepository->save($identity);
    }
}

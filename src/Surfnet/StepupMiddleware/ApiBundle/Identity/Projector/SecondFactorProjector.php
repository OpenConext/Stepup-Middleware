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
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRepository;

class SecondFactorProjector extends Projector
{
    /**
     * @var SecondFactorRepository
     */
    private $secondFactorRepository;

    public function __construct(SecondFactorRepository $secondFactorRepository)
    {
        $this->secondFactorRepository = $secondFactorRepository;
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->secondFactorRepository->proveYubikeyPossession(
            $event->identityId,
            $event->secondFactorId,
            $event->yubikeyPublicId
        );
    }
}

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
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;

class UnverifiedSecondFactorProjector extends Projector
{
    /**
     * @var UnverifiedSecondFactorRepository
     */
    private $unverifiedSecondFactorRepository;

    public function __construct(UnverifiedSecondFactorRepository $unverifiedSecondFactorRepository)
    {
        $this->unverifiedSecondFactorRepository = $unverifiedSecondFactorRepository;
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->unverifiedSecondFactorRepository->proveYubikeyPossession(
            $event->identityId,
            $event->secondFactorId,
            $event->yubikeyPublicId,
            $event->emailVerificationNonce
        );
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->unverifiedSecondFactorRepository->provePhonePossession(
            $event->identityId,
            $event->secondFactorId,
            $event->phoneNumber,
            $event->emailVerificationNonce
        );
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->unverifiedSecondFactorRepository->verifyEmail($event->secondFactorId);
    }
}

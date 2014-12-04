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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;

class SecondFactorProjector extends Projector
{
    /**
     * @var UnverifiedSecondFactorRepository
     */
    private $unverifiedRepository;

    /**
     * @var VerifiedSecondFactorRepository
     */
    private $verifiedRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        UnverifiedSecondFactorRepository $unverifiedRepository,
        VerifiedSecondFactorRepository $verifiedRepository,
        IdentityRepository $identityRepository
    ) {
        $this->unverifiedRepository = $unverifiedRepository;
        $this->verifiedRepository = $verifiedRepository;
        $this->identityRepository = $identityRepository;
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->unverifiedRepository->save(
            UnverifiedSecondFactor::addToIdentity(
                $identity,
                (string) $event->secondFactorId,
                'yubikey',
                (string) $event->yubikeyPublicId
            )
        );
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->unverifiedRepository->save(
            UnverifiedSecondFactor::addToIdentity(
                $identity,
                (string) $event->secondFactorId,
                'sms',
                (string) $event->phoneNumber
            )
        );
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $unverified = $this->unverifiedRepository->find((string) $event->secondFactorId);

        $this->verifiedRepository->save($unverified->verifyEmail($event->registrationCode));
        $this->unverifiedRepository->remove($unverified);
    }
}

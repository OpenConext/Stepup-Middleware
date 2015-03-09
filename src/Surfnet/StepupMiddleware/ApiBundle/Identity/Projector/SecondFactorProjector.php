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
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecondFactorProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository
     */
    private $unverifiedRepository;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository
     */
    private $verifiedRepository;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository
     */
    private $vettedRepository;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        UnverifiedSecondFactorRepository $unverifiedRepository,
        VerifiedSecondFactorRepository $verifiedRepository,
        VettedSecondFactorRepository $vettedRepository,
        IdentityRepository $identityRepository
    ) {
        $this->unverifiedRepository = $unverifiedRepository;
        $this->verifiedRepository = $verifiedRepository;
        $this->vettedRepository = $vettedRepository;
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
                (string) $event->yubikeyPublicId,
                $event->emailVerificationNonce,
                $event->emailVerificationWindow->openUntil()
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
                (string) $event->phoneNumber,
                $event->emailVerificationNonce,
                $event->emailVerificationWindow->openUntil()
            )
        );
    }

    public function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string)$event->identityId);

        $this->unverifiedRepository->save(
            UnverifiedSecondFactor::addToIdentity(
                $identity,
                (string) $event->secondFactorId,
                (string) $event->stepupProvider,
                (string) $event->gssfId,
                $event->emailVerificationNonce,
                $event->emailVerificationWindow->openUntil()
            )
        );
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $unverified = $this->unverifiedRepository->find((string) $event->secondFactorId);

        $this->verifiedRepository->save($unverified->verifyEmail($event->registrationCode));
        $this->unverifiedRepository->remove($unverified);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $verified = $this->verifiedRepository->find((string) $event->secondFactorId);

        $this->vettedRepository->save($verified->vet());
        $this->verifiedRepository->remove($verified);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->unverifiedRepository->remove($this->unverifiedRepository->find((string) $event->secondFactorId));
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->unverifiedRepository->remove($this->unverifiedRepository->find((string) $event->secondFactorId));
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->verifiedRepository->remove($this->verifiedRepository->find((string) $event->secondFactorId));
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->verifiedRepository->remove($this->verifiedRepository->find((string) $event->secondFactorId));
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->vettedRepository->remove($this->vettedRepository->find((string) $event->secondFactorId));
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->vettedRepository->remove($this->vettedRepository->find((string) $event->secondFactorId));
    }
}

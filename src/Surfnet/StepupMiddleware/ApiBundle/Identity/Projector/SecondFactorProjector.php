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
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
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

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $secondFactor = new VettedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'yubikey';
        $secondFactor->secondFactorIdentifier = $event->yubikeyPublicId->getValue();

        $this->vettedRepository->save($secondFactor);
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'yubikey';
        $secondFactor->secondFactorIdentifier = $event->yubikeyPublicId->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;
        $secondFactor->verificationNonceValidUntil = $event->emailVerificationWindow->openUntil();

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'sms';
        $secondFactor->secondFactorIdentifier = $event->phoneNumber->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;
        $secondFactor->verificationNonceValidUntil = $event->emailVerificationWindow->openUntil();

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = $event->stepupProvider->getStepupProvider();
        $secondFactor->secondFactorIdentifier = $event->gssfId->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;
        $secondFactor->verificationNonceValidUntil = $event->emailVerificationWindow->openUntil();

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event)
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'u2f';
        $secondFactor->secondFactorIdentifier = $event->keyHandle->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;
        $secondFactor->verificationNonceValidUntil = $event->emailVerificationWindow->openUntil();

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $unverified = $this->unverifiedRepository->find($event->secondFactorId->getSecondFactorId());

        $verified = new VerifiedSecondFactor();
        $verified->id = $event->secondFactorId->getSecondFactorId();
        $verified->identityId = $event->identityId->getIdentityId();
        $verified->institution = $event->identityInstitution->getInstitution();
        $verified->commonName = $event->commonName->getCommonName();
        $verified->type = $event->secondFactorType->getSecondFactorType();
        $verified->secondFactorIdentifier = $unverified->secondFactorIdentifier;
        $verified->registrationCode = $event->registrationCode;

        $this->verifiedRepository->save($verified);
        $this->unverifiedRepository->remove($unverified);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $verified = $this->verifiedRepository->find($event->secondFactorId->getSecondFactorId());

        $vetted = new VettedSecondFactor();
        $vetted->id = $event->secondFactorId->getSecondFactorId();
        $vetted->identityId = $event->identityId->getIdentityId();
        $vetted->type = $event->secondFactorType->getSecondFactorType();
        $vetted->secondFactorIdentifier = $event->secondFactorIdentifier->getValue();

        $this->vettedRepository->save($vetted);
        $this->verifiedRepository->remove($verified);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->unverifiedRepository->remove($this->unverifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->unverifiedRepository->remove($this->unverifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->verifiedRepository->remove($this->verifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->verifiedRepository->remove($this->verifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->vettedRepository->remove($this->vettedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->vettedRepository->remove($this->vettedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->unverifiedRepository->removeByIdentityId($event->identityId);
        $this->verifiedRepository->removeByIdentityId($event->identityId);
        $this->vettedRepository->removeByIdentityId($event->identityId);
    }
}

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
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SecondFactorProjector extends Projector
{
    public function __construct(
        private readonly UnverifiedSecondFactorRepository $unverifiedRepository,
        private readonly VerifiedSecondFactorRepository $verifiedRepository,
        private readonly VettedSecondFactorRepository $vettedRepository,
    ) {
    }

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event): void
    {
        $secondFactor = new VettedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'yubikey';
        $secondFactor->secondFactorIdentifier = $event->yubikeyPublicId->getValue();
        $secondFactor->vettingType = VettingType::TYPE_ON_PREMISE;
        $this->vettedRepository->save($secondFactor);
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event): void
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'yubikey';
        $secondFactor->secondFactorIdentifier = $event->yubikeyPublicId->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyYubikeyPossessionProvenAndVerifiedEvent(YubikeyPossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = new VerifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->institution = $event->identityInstitution->getInstitution();
        $secondFactor->type = 'yubikey';
        $secondFactor->secondFactorIdentifier = $event->yubikeyPublicId->getValue();
        $secondFactor->commonName = $event->commonName;
        $secondFactor->registrationRequestedAt = $event->registrationRequestedAt;
        $secondFactor->registrationCode = $event->registrationCode;

        $this->verifiedRepository->save($secondFactor);
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event): void
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = 'sms';
        $secondFactor->secondFactorIdentifier = $event->phoneNumber->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyPhonePossessionProvenAndVerifiedEvent(PhonePossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = new VerifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->institution = $event->identityInstitution->getInstitution();
        $secondFactor->type = 'sms';
        $secondFactor->secondFactorIdentifier = $event->phoneNumber->getValue();
        $secondFactor->commonName = $event->commonName;
        $secondFactor->registrationRequestedAt = $event->registrationRequestedAt;
        $secondFactor->registrationCode = $event->registrationCode;

        $this->verifiedRepository->save($secondFactor);
    }

    public function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event): void
    {
        $secondFactor = new UnverifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->type = $event->stepupProvider->getStepupProvider();
        $secondFactor->secondFactorIdentifier = $event->gssfId->getValue();
        $secondFactor->verificationNonce = $event->emailVerificationNonce;

        $this->unverifiedRepository->save($secondFactor);
    }

    public function applyGssfPossessionProvenAndVerifiedEvent(GssfPossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = new VerifiedSecondFactor();
        $secondFactor->id = $event->secondFactorId->getSecondFactorId();
        $secondFactor->identityId = $event->identityId->getIdentityId();
        $secondFactor->institution = $event->identityInstitution->getInstitution();
        $secondFactor->type = $event->stepupProvider->getStepupProvider();
        $secondFactor->secondFactorIdentifier = $event->gssfId->getValue();
        $secondFactor->commonName = $event->commonName;
        $secondFactor->registrationRequestedAt = $event->registrationRequestedAt;
        $secondFactor->registrationCode = $event->registrationCode;

        $this->verifiedRepository->save($secondFactor);
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event): void
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
        $verified->registrationRequestedAt = $event->registrationRequestedAt;

        $this->verifiedRepository->save($verified);
        $this->unverifiedRepository->remove($unverified);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $verified = $this->verifiedRepository->find($event->secondFactorId->getSecondFactorId());

        $vetted = new VettedSecondFactor();
        $vetted->id = $event->secondFactorId->getSecondFactorId();
        $vetted->identityId = $event->identityId->getIdentityId();
        $vetted->type = $event->secondFactorType->getSecondFactorType();
        $vetted->secondFactorIdentifier = $event->secondFactorIdentifier->getValue();
        // In case the vetting type is unknown (for example when no event replay was performed)
        // fall back to the unknown vetting type.
        $vettingType = $event->vettingType;
        if (!$vettingType) {
            $vettingType = new UnknownVettingType();
        }
        $vetted->vettingType = $vettingType->type();

        $this->vettedRepository->save($vetted);
        $this->verifiedRepository->remove($verified);
    }

    /**
     * A new vetted second factor is projected. A copy of the 'source' second factor.
     * The original 'source' second factor is not yet removed. This is handled when the
     * old identity is cleaned up.
     */
    public function applySecondFactorMigratedEvent(SecondFactorMigratedEvent $event): void
    {
        $vetted = new VettedSecondFactor();
        $vetted->id = $event->newSecondFactorId->getSecondFactorId();
        $vetted->identityId = $event->identityId->getIdentityId();
        $vetted->type = $event->secondFactorType->getSecondFactorType();
        $vettingType = $event->vettingType ?? new UnknownVettingType();
        $vetted->vettingType = $vettingType;
        $vetted->secondFactorIdentifier = $event->secondFactorIdentifier->getValue();
        $this->vettedRepository->save($vetted);
    }

    public function applySecondFactorVettedWithoutTokenProofOfPossession(
        SecondFactorVettedWithoutTokenProofOfPossession $event,
    ): void {
        $verified = $this->verifiedRepository->find($event->secondFactorId->getSecondFactorId());

        $vetted = new VettedSecondFactor();
        $vetted->id = $event->secondFactorId->getSecondFactorId();
        $vetted->identityId = $event->identityId->getIdentityId();
        $vetted->type = $event->secondFactorType->getSecondFactorType();
        $vetted->secondFactorIdentifier = $event->secondFactorIdentifier->getValue();
        $vettingType = $event->vettingType;
        // In case the vetting type is unknown (for example when no event replay was performed)
        // fall back to the unknown vetting type.
        if (!$vettingType) {
            $vettingType = new UnknownVettingType();
        }
        $vetted->vettingType = $vettingType->type();

        $this->vettedRepository->save($vetted);
        $this->verifiedRepository->remove($verified);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event): void
    {
        $this->unverifiedRepository->remove(
            $this->unverifiedRepository->find($event->secondFactorId->getSecondFactorId()),
        );
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event,
    ): void
    {
        $this->unverifiedRepository->remove(
            $this->unverifiedRepository->find($event->secondFactorId->getSecondFactorId()),
        );
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event): void
    {
        $this->verifiedRepository->remove($this->verifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event,
    ): void
    {
        $this->verifiedRepository->remove($this->verifiedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event): void
    {
        $this->vettedRepository->remove($this->vettedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event,
    ): void
    {
        $this->vettedRepository->remove($this->vettedRepository->find($event->secondFactorId->getSecondFactorId()));
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $this->unverifiedRepository->removeByIdentityId($event->identityId);
        $this->verifiedRepository->removeByIdentityId($event->identityId);
        $this->vettedRepository->removeByIdentityId($event->identityId);
    }
}

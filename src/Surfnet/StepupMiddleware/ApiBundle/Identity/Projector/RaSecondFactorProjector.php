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
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaSecondFactorProjector extends Projector
{
    public function __construct(
        private readonly RaSecondFactorRepository $raSecondFactorRepository,
        private readonly IdentityRepository $identityRepository,
    ) {
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event): void
    {
        $secondFactors = $this->raSecondFactorRepository->findByIdentityId((string)$event->identityId);

        if ($secondFactors === []) {
            return;
        }

        $commonName = $event->commonName;

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->name = $commonName;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event): void
    {
        $secondFactors = $this->raSecondFactorRepository->findByIdentityId((string)$event->identityId);

        if ($secondFactors === []) {
            return;
        }

        $email = $event->email;

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->email = $email;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
    }

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event): void
    {
        $identity = $this->identityRepository->find((string)$event->identityId);

        $secondFactor = new RaSecondFactor(
            (string)$event->secondFactorId,
            'yubikey',
            (string)$event->yubikeyPublicId,
            $identity->id,
            $identity->institution,
            $event->commonName,
            $event->email,
        );
        $secondFactor->status = SecondFactorStatus::vetted();

        $this->raSecondFactorRepository->save($secondFactor);
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'yubikey',
            (string)$event->yubikeyPublicId,
            $event->commonName,
            $event->email,
        );
    }

    public function applyYubikeyPossessionProvenAndVerifiedEvent(YubikeyPossessionProvenAndVerifiedEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'yubikey',
            (string)$event->yubikeyPublicId,
            $event->commonName,
            $event->email,
        );
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'sms',
            (string)$event->phoneNumber,
            $event->commonName,
            $event->email,
        );
    }

    public function applyPhonePossessionProvenAndVerifiedEvent(PhonePossessionProvenAndVerifiedEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'sms',
            (string)$event->phoneNumber,
            $event->commonName,
            $event->email,
        );
    }

    public function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            (string)$event->stepupProvider,
            (string)$event->gssfId,
            $event->commonName,
            $event->email,
        );
    }

    public function applyGssfPossessionProvenAndVerifiedEvent(GssfPossessionProvenAndVerifiedEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            (string)$event->stepupProvider,
            (string)$event->gssfId,
            $event->commonName,
            $event->email,
        );
    }

    /**
     * @deprecated Built in U2F support is dropped from StepUp, this was not removed to support event replay
     */
    public function applyU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'u2f',
            $event->keyHandle->getValue(),
            $event->commonName,
            $event->email,
        );
    }

    /**
     * @deprecated Built in U2F support is dropped from StepUp, this was not removed to support event replay
     */
    public function applyU2fDevicePossessionProvenAndVerifiedEvent(U2fDevicePossessionProvenAndVerifiedEvent $event,): void
    {
        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->secondFactorId,
            'u2f',
            $event->keyHandle->getValue(),
            $event->commonName,
            $event->email,
        );
    }

    /**
     * @param SecondFactorStatus|null $status
     * @param DocumentNumber|null $documentNumber
     */
    private function saveRaSecondFactor(
        string             $identityId,
        string             $secondFactorId,
        string             $secondFactorType,
        string             $secondFactorIdentifier,
        CommonName         $commonName,
        Email              $email,
        SecondFactorStatus $status = null,
        DocumentNumber     $documentNumber = null,
    ): void {
        $identity = $this->identityRepository->find($identityId);

        $secondFactor = new RaSecondFactor(
            $secondFactorId,
            $secondFactorType,
            $secondFactorIdentifier,
            $identity->id,
            $identity->institution,
            $commonName,
            $email,
            $documentNumber,
        );

        if ($status instanceof SecondFactorStatus) {
            $secondFactor->status = $status;
        }

        $this->raSecondFactorRepository->save($secondFactor);
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event): void
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::verified());
    }

    /**
     * The RA second factor projection is updated with a new Second factor based on the 'source' second factor
     * from the original identity.
     */
    public function applySecondFactorMigratedEvent(SecondFactorMigratedEvent $event): void
    {
        $oldSecondFactor = $this->raSecondFactorRepository->find((string)$event->secondFactorId);

        $this->saveRaSecondFactor(
            (string)$event->identityId,
            (string)$event->newSecondFactorId,
            (string)$event->secondFactorType,
            (string)$event->secondFactorIdentifier,
            $event->commonName,
            $event->email,
            $oldSecondFactor->status,
            $oldSecondFactor->documentNumber,
        );
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $secondFactor = $this->raSecondFactorRepository->find((string)$event->secondFactorId);
        $secondFactor->documentNumber = $event->vettingType->getDocumentNumber();
        $secondFactor->status = SecondFactorStatus::vetted();

        $this->raSecondFactorRepository->save($secondFactor);
    }

    public function applySecondFactorVettedWithoutTokenProofOfPossession(
        SecondFactorVettedWithoutTokenProofOfPossession $event,
    ): void {
        $secondFactor = $this->raSecondFactorRepository->find((string)$event->secondFactorId);

        $documentNumber = null;
        if ($event->vettingType instanceof OnPremiseVettingType) {
            $documentNumber = $event->vettingType->getDocumentNumber();
        }
        $secondFactor->documentNumber = $documentNumber;
        $secondFactor->status = SecondFactorStatus::vetted();

        $this->raSecondFactorRepository->save($secondFactor);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event): void
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event,
    ): void {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event): void
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event,
    ): void {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event): void
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event,
    ): void {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $this->raSecondFactorRepository->updateStatusByIdentityIdToForgotten($event->identityId);
    }

    private function updateStatus(SecondFactorId $secondFactorId, SecondFactorStatus $status): void
    {
        $secondFactor = $this->raSecondFactorRepository->find((string)$secondFactorId);
        $secondFactor->status = $status;

        $this->raSecondFactorRepository->save($secondFactor);
    }
}

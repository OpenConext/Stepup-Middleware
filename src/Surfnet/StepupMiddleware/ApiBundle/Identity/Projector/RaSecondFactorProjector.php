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
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RaSecondFactorProjector extends Projector
{
    /**
     * @var RaSecondFactorRepository
     */
    private $raSecondFactorRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        RaSecondFactorRepository $raSecondFactorRepository,
        IdentityRepository $identityRepository
    ) {
        $this->raSecondFactorRepository = $raSecondFactorRepository;
        $this->identityRepository = $identityRepository;
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $secondFactors = $this->raSecondFactorRepository->findByIdentityId((string) $event->identityId);

        if (count($secondFactors) === 0) {
            return;
        }

        $commonName = $event->commonName;

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->name = $commonName;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $secondFactors = $this->raSecondFactorRepository->findByIdentityId((string) $event->identityId);

        if (count($secondFactors) === 0) {
            return;
        }

        $email = $event->email;

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->email = $email;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
    }

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $secondFactor = new RaSecondFactor(
            (string) $event->secondFactorId,
            'yubikey',
            (string) $event->yubikeyPublicId,
            $identity->id,
            $identity->institution,
            $event->commonName,
            $event->email
        );
        $secondFactor->status = SecondFactorStatus::vetted();

        $this->raSecondFactorRepository->save($secondFactor);
    }

    public function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->raSecondFactorRepository->save(
            new RaSecondFactor(
                (string) $event->secondFactorId,
                'yubikey',
                (string) $event->yubikeyPublicId,
                $identity->id,
                $identity->institution,
                $event->commonName,
                $event->email
            )
        );
    }

    public function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->raSecondFactorRepository->save(
            new RaSecondFactor(
                (string) $event->secondFactorId,
                'sms',
                (string) $event->phoneNumber,
                $identity->id,
                $identity->institution,
                $event->commonName,
                $event->email
            )
        );
    }

    public function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->raSecondFactorRepository->save(
            new RaSecondFactor(
                (string) $event->secondFactorId,
                (string) $event->stepupProvider,
                (string) $event->gssfId,
                $identity->id,
                $identity->institution,
                $event->commonName,
                $event->email
            )
        );
    }

    public function applyU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $this->raSecondFactorRepository->save(
            new RaSecondFactor(
                (string) $event->secondFactorId,
                'u2f',
                $event->keyHandle->getValue(),
                $identity->id,
                $identity->institution,
                $event->commonName,
                $event->email
            )
        );
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::verified());
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::vetted());
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, SecondFactorStatus::revoked());
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->raSecondFactorRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param SecondFactorId $secondFactorId
     * @param SecondFactorStatus $status
     */
    private function updateStatus(SecondFactorId $secondFactorId, SecondFactorStatus $status)
    {
        $secondFactor = $this->raSecondFactorRepository->find((string) $secondFactorId);
        $secondFactor->status = $status;

        $this->raSecondFactorRepository->save($secondFactor);
    }
}

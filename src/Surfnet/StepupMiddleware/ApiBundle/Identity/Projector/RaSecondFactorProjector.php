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
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRepository;

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

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->name = $event->newName;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $secondFactors = $this->raSecondFactorRepository->findByIdentityId((string) $event->identityId);

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->email = $event->newEmail;
        }

        $this->raSecondFactorRepository->saveAll($secondFactors);
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
                (string) $identity->institution,
                $identity->commonName,
                $identity->email
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
                (string) $identity->institution,
                $identity->commonName,
                $identity->email
            )
        );
    }

    public function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_VERIFIED);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_VETTED);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->updateStatus($event->secondFactorId, RaSecondFactor::STATUS_REVOKED);
    }

    /**
     * @param SecondFactorId $secondFactorId
     * @param string $status One of the RaSecondFactor::STATUS_* constants.
     */
    private function updateStatus(SecondFactorId $secondFactorId, $status)
    {
        $secondFactor = $this->raSecondFactorRepository->find((string) $secondFactorId);
        $secondFactor->status = $status;

        $this->raSecondFactorRepository->save($secondFactor);
    }
}

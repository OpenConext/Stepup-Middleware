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

namespace Surfnet\StepupMiddleware\GatewayBundle\Projector;

use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\Stepup\Projector\Projector;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupMiddleware\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\SecondFactorRepository;

class SecondFactorProjector extends Projector
{
    public function __construct(private readonly SecondFactorRepository $repository)
    {
    }

    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event): void
    {
        $this->repository->save(
            new SecondFactor(
                (string)$event->identityId,
                (string)$event->nameId,
                (string)$event->identityInstitution,
                (string)$event->preferredLocale,
                (string)$event->secondFactorId,
                (string)$event->yubikeyPublicId,
                'yubikey',
                true,
            ),
        );
    }

    public function applySecondFactorMigratedEvent(SecondFactorMigratedEvent $event): void
    {
        $this->repository->save(
            new SecondFactor(
                (string)$event->identityId,
                (string)$event->targetNameId,
                (string)$event->identityInstitution,
                (string)$event->preferredLocale,
                (string)$event->newSecondFactorId,
                $event->secondFactorIdentifier,
                $event->secondFactorType,
                $this->isIdentityVetted($event->vettingType),
            ),
        );
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $this->repository->save(
            new SecondFactor(
                (string)$event->identityId,
                (string)$event->nameId,
                (string)$event->identityInstitution,
                (string)$event->preferredLocale,
                (string)$event->secondFactorId,
                $event->secondFactorIdentifier,
                $event->secondFactorType,
                $this->isIdentityVetted($event->vettingType),
            ),
        );
    }

    public function applySecondFactorVettedWithoutTokenProofOfPossession(
        SecondFactorVettedWithoutTokenProofOfPossession $event,
    ): void {
        $this->repository->save(
            new SecondFactor(
                (string)$event->identityId,
                (string)$event->nameId,
                (string)$event->identityInstitution,
                (string)$event->preferredLocale,
                (string)$event->secondFactorId,
                $event->secondFactorIdentifier,
                $event->secondFactorType,
                $this->isIdentityVetted($event->vettingType),
            ),
        );
    }

    private function isIdentityVetted(VettingType $vettingType): bool
    {
        return $vettingType->type() !== VettingType::TYPE_SELF_ASSERTED_REGISTRATION;
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event): void
    {
        $secondFactor = $this->repository->findOneBySecondFactorId($event->secondFactorId);

        if ($secondFactor === null) {
            throw new RuntimeException(
                sprintf(
                    'Expected to find a second factor having secondFactorId "%s", found none.',
                    $event->secondFactorId,
                ),
            );
        }

        $this->repository->remove($secondFactor);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event,
    ): void {
        $secondFactor = $this->repository->findOneBySecondFactorId($event->secondFactorId);

        if ($secondFactor === null) {
            throw new RuntimeException(
                sprintf(
                    'Expected to find a second factor having secondFactorId "%s", found none.',
                    $event->secondFactorId,
                ),
            );
        }

        $this->repository->remove($secondFactor);
    }

    protected function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event): void
    {
        $secondFactors = $this->repository->findByIdentityId($event->identityId);

        foreach ($secondFactors as $secondFactor) {
            $secondFactor->displayLocale = (string)$event->preferredLocale;
            $this->repository->save($secondFactor);
        }
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $this->repository->removeByIdentityId($event->identityId);
    }
}

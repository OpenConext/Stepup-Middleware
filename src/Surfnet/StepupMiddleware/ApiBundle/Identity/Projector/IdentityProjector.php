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
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityProjector extends Projector
{
    private IdentityRepository $identityRepository;

    public function __construct(IdentityRepository $identityRepository)
    {
        $this->identityRepository = $identityRepository;
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event): void
    {
        $this->identityRepository->save(Identity::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->email,
            $event->commonName,
            $event->preferredLocale
        ));
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->commonName = $event->commonName;

        $this->identityRepository->save($identity);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->email = $event->email;

        $this->identityRepository->save($identity);
    }

    public function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->preferredLocale = $event->preferredLocale;

        $this->identityRepository->save($identity);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $this->determinePossessionOfSelfAssertedToken($event->vettingType, (string) $event->identityId);
    }

    public function applySecondFactorVettedWithoutTokenProofOfPossession(SecondFactorVettedWithoutTokenProofOfPossession $event): void
    {
        $this->determinePossessionOfSelfAssertedToken($event->vettingType, (string) $event->identityId);
    }

    private function determinePossessionOfSelfAssertedToken(VettingType $vettingType, string $identityId): void
    {
        if ($vettingType->type() === VettingType::TYPE_SELF_ASSERTED_REGISTRATION) {
            $identity = $this->identityRepository->find($identityId);
            if ($identity instanceof Identity) {
                $identity->possessedSelfAssertedToken = true;
                $this->identityRepository->save($identity);
            }
        }
    }
}

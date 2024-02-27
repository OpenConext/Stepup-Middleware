<?php

/**
 * Copyright 2022 SURFnet bv
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
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\IdentitySelfAssertedTokenOptions;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentitySelfAssertedTokenOptionsRepository;

class IdentitySelfAssertedTokenOptionsProjector extends Projector
{
    public function __construct(
        private readonly IdentitySelfAssertedTokenOptionsRepository $repository,
    ) {
    }

    /**
     * Identity is created, we also create a set of
     * IdentitySelfAssertedTokenOptions.
     */
    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event): void
    {
        $identitySelfAssertedTokenOptions = IdentitySelfAssertedTokenOptions::create(
            $event->identityId,
            false,
            false,
        );
        $this->repository->save($identitySelfAssertedTokenOptions);
    }

    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $this->determinePossessionOfToken($event->vettingType, $event->identityId);
    }

    public function applySecondFactorVettedWithoutTokenProofOfPossession(
        SecondFactorVettedWithoutTokenProofOfPossession $event,
    ): void {
        $this->determinePossessionOfToken($event->vettingType, $event->identityId);
    }

    private function determinePossessionOfToken(VettingType $vettingType, IdentityId $identityId): void
    {
        $isSelfAssertedToken = $vettingType->type() === VettingType::TYPE_SELF_ASSERTED_REGISTRATION;
        $identitySelfAssertedTokenOptions = $this->repository->find($identityId);
        // Scenario 1: A new token is registered, we have no sat options yet,
        // create them. These are identities from the pre SAT era.
        if (!$identitySelfAssertedTokenOptions instanceof IdentitySelfAssertedTokenOptions) {
            $identitySelfAssertedTokenOptions = IdentitySelfAssertedTokenOptions::create(
                $identityId,
                true,
                $isSelfAssertedToken,
            );
            $this->repository->save($identitySelfAssertedTokenOptions);
            return;
        }
        // Scenario 2: handle vetting of an Identity with IdentitySelfAssertedTokenOptions
        if ($vettingType->type() === VettingType::TYPE_SELF_ASSERTED_REGISTRATION) {
            $identitySelfAssertedTokenOptions->possessedSelfAssertedToken = true;
        }
        $identitySelfAssertedTokenOptions->possessedToken = true;
        $this->repository->save($identitySelfAssertedTokenOptions);
    }
}

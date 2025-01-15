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
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RecoveryToken;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

/**
 * Project RecoveryTokens that are successfully registered by an Identity
 */
class RecoveryTokenProjector extends Projector
{
    public function __construct(private readonly RecoveryTokenRepository $recoveryTokenRepository)
    {
    }

    public function applyPhoneRecoveryTokenPossessionProvenEvent(PhoneRecoveryTokenPossessionProvenEvent $event): void
    {
        $recoveryToken = new RecoveryToken();
        $recoveryToken->id = $event->recoveryTokenId->getRecoveryTokenId();
        $recoveryToken->identityId = $event->identityId->getIdentityId();
        $recoveryToken->type = RecoveryTokenType::TYPE_SMS;
        $recoveryToken->status = RecoveryTokenStatus::active();
        $recoveryToken->recoveryMethodIdentifier = (string)$event->phoneNumber;
        $recoveryToken->institution = $event->identityInstitution;
        $recoveryToken->email = $event->email;
        $recoveryToken->name = $event->commonName;

        $this->recoveryTokenRepository->save($recoveryToken);
    }

    public function applySafeStoreSecretRecoveryTokenPossessionPromisedEvent(
        SafeStoreSecretRecoveryTokenPossessionPromisedEvent $event,
    ): void {
        $recoveryToken = new RecoveryToken();
        $recoveryToken->id = $event->recoveryTokenId->getRecoveryTokenId();
        $recoveryToken->identityId = $event->identityId->getIdentityId();
        $recoveryToken->type = RecoveryTokenType::TYPE_SAFE_STORE;
        $recoveryToken->status = RecoveryTokenStatus::active();
        $recoveryToken->recoveryMethodIdentifier = (string)$event->secret;
        $recoveryToken->institution = $event->identityInstitution;
        $recoveryToken->email = $event->email;
        $recoveryToken->name = $event->commonName;

        $this->recoveryTokenRepository->save($recoveryToken);
    }

    public function applyCompliedWithRecoveryCodeRevocationEvent(CompliedWithRecoveryCodeRevocationEvent $event): void
    {
        $token = $this->recoveryTokenRepository->find((string)$event->recoveryTokenId);
        $token->status = RecoveryTokenStatus::revoked();
        $this->recoveryTokenRepository->save($token);
    }

    public function applyRecoveryTokenRevokedEvent(RecoveryTokenRevokedEvent $event): void
    {
        $token = $this->recoveryTokenRepository->find((string)$event->recoveryTokenId);
        $token->status = RecoveryTokenStatus::revoked();
        $this->recoveryTokenRepository->save($token);
    }

    /**
     * When Identity is forgotten, the recovery token projections for this identity
     * are removed from the recovery_tokens table.
     */
    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $this->recoveryTokenRepository->removeByIdentity($event->identityId);
    }
}

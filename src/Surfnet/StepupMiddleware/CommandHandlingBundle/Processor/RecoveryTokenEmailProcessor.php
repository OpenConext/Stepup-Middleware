<?php

/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Processor;

use Broadway\Processor\Processor;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RecoveryTokenMailService;

final class RecoveryTokenEmailProcessor extends Processor
{
    public function __construct(
        private readonly RecoveryTokenMailService $mailService,
        private readonly IdentityService $identityService,
    ) {
    }

    public function handleCompliedWithRecoveryCodeRevocationEvent(
        CompliedWithRecoveryCodeRevocationEvent $event,
    ): void {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if (!$identity instanceof Identity) {
            return;
        }

        $this->mailService->sendRevoked(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->recoveryTokenType,
            $event->recoveryTokenId,
            true,
        );
    }

    public function handleRecoveryTokenRevokedEvent(RecoveryTokenRevokedEvent $event): void
    {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if (!$identity instanceof Identity) {
            return;
        }

        $this->mailService->sendRevoked(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->recoveryTokenType,
            $event->recoveryTokenId,
            false,
        );
    }

    public function handlePhoneRecoveryTokenPossessionProvenEvent(PhoneRecoveryTokenPossessionProvenEvent $event): void
    {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if (!$identity instanceof Identity) {
            return;
        }
        $this->mailService->sendCreated(
            $identity->preferredLocale,
            $event->commonName,
            $event->email,
        );
    }

    public function handleSafeStoreSecretRecoveryTokenPossessionPromisedEvent(
        SafeStoreSecretRecoveryTokenPossessionPromisedEvent $event,
    ): void {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if (!$identity instanceof Identity) {
            return;
        }
        $this->mailService->sendCreated(
            $identity->preferredLocale,
            $event->commonName,
            $event->email,
        );
    }
}

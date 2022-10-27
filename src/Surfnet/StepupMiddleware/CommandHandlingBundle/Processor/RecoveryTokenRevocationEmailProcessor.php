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
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RecoveryTokenMailService;

final class RecoveryTokenRevocationEmailProcessor extends Processor
{
    /**
     * @var RecoveryTokenMailService
     */
    private $mailService;

    /**
     * @var IdentityService
     */
    private $identityService;

    public function __construct(
        RecoveryTokenMailService $recoveryTokenMailService,
        IdentityService $identityService
    ) {
        $this->mailService = $recoveryTokenMailService;
        $this->identityService = $identityService;
    }

    public function handleCompliedWithRecoveryCodeRevocationEvent(
        CompliedWithRecoveryCodeRevocationEvent $event
    ) {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if ($identity === null) {
            return;
        }

        $this->mailService->sendRevoked(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->recoveryTokenType,
            $event->recoveryTokenId,
            true
        );
    }

    public function handleRecoveryTokenRevokedEvent(RecoveryTokenRevokedEvent $event)
    {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if ($identity === null) {
            return;
        }

        $this->mailService->sendRevoked(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->recoveryTokenType,
            $event->recoveryTokenId,
            false
        );
    }
}

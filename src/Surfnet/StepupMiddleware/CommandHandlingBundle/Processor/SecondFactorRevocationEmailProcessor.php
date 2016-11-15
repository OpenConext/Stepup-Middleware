<?php

/**
 * Copyright 2016 SURFnet B.V.
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
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorRevokedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorMailService;

final class SecondFactorRevocationEmailProcessor extends Processor
{
    /**
     * @var SecondFactorMailService
     */
    private $mailService;

    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @param SecondFactorMailService $mailService
     * @param IdentityService $identityService
     */
    public function __construct(SecondFactorMailService $mailService, IdentityService $identityService)
    {
        $this->mailService = $mailService;
        $this->identityService = $identityService;
    }

    public function handleCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if ($identity === null) {
            return;
        }

        $this->mailService->sendVettedSecondFactorRevokedByRaEmail(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->secondFactorType,
            $event->secondFactorIdentifier
        );
    }

    public function handleVettedSecondFactorRevokedEvent(SecondFactorRevokedEvent $event)
    {
        $identity = $this->identityService->find($event->identityId->getIdentityId());

        if ($identity === null) {
            return;
        }

        $this->mailService->sendVettedSecondFactorRevokedByRegistrantEmail(
            $identity->preferredLocale,
            $identity->commonName,
            $identity->email,
            $event->secondFactorType,
            $event->secondFactorIdentifier
        );
    }
}

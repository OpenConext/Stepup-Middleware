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
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\EmailVerificationMailService;

final class EmailVerificationEmailProcessor extends Processor
{
    private EmailVerificationMailService $emailVerificationMailService;

    public function __construct(EmailVerificationMailService $emailVerificationMailService)
    {
        $this->emailVerificationMailService = $emailVerificationMailService;
    }

    public function handlePhonePossessionProvenEvent(PhonePossessionProvenEvent $event): void
    {
        if ($event->emailVerificationRequired !== false) {
            $this->emailVerificationMailService->sendEmailVerificationEmail(
                (string) $event->preferredLocale,
                (string) $event->commonName,
                (string) $event->email,
                $event->emailVerificationNonce
            );
        }
    }

    public function handleYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event): void
    {
        if ($event->emailVerificationRequired !== false) {
            $this->emailVerificationMailService->sendEmailVerificationEmail(
                (string) $event->preferredLocale,
                (string) $event->commonName,
                (string) $event->email,
                $event->emailVerificationNonce
            );
        }
    }

    public function handleGssfPossessionProvenEvent(GssfPossessionProvenEvent $event): void
    {
        if ($event->emailVerificationRequired !== false) {
            $this->emailVerificationMailService->sendEmailVerificationEmail(
                (string) $event->preferredLocale,
                (string) $event->commonName,
                (string) $event->email,
                $event->emailVerificationNonce
            );
        }
    }
}

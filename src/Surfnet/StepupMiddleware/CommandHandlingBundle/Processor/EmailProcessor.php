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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Processor;

use Broadway\Processor\Processor;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Service\VettingLocationService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Institution;

class EmailProcessor extends Processor
{
    /**
     * @var SecondFactorMailService
     */
    private $mailService;

    /**
     * @var VettingLocationService
     */
    private $vettingLocationService;

    /**
     * @param SecondFactorMailService $mailService
     * @param VettingLocationService $vettingLocationService
     */
    public function __construct(SecondFactorMailService $mailService, VettingLocationService $vettingLocationService)
    {
        $this->mailService            = $mailService;
        $this->vettingLocationService = $vettingLocationService;
    }

    public function handlePhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->mailService->sendEmailVerificationEmail(
            (string) $event->preferredLocale,
            (string) $event->commonName,
            (string) $event->email,
            $event->emailVerificationNonce
        );
    }

    public function handleYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->mailService->sendEmailVerificationEmail(
            (string) $event->preferredLocale,
            (string) $event->commonName,
            (string) $event->email,
            $event->emailVerificationNonce
        );
    }

    public function handleGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $this->mailService->sendEmailVerificationEmail(
            (string) $event->preferredLocale,
            (string) $event->commonName,
            (string) $event->email,
            $event->emailVerificationNonce
        );
    }

    public function handleU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event)
    {
        $this->mailService->sendEmailVerificationEmail(
            (string) $event->preferredLocale,
            (string) $event->commonName,
            (string) $event->email,
            $event->emailVerificationNonce
        );
    }

    public function handleEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->mailService->sendRegistrationEmail(
            (string) $event->preferredLocale,
            (string) $event->commonName,
            (string) $event->email,
            $event->registrationCode,
            $this->vettingLocationService->getVettingLocationsFor(
                new Institution($event->identityInstitution->getInstitution())
            )
        );
    }

    public function handleSecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $this->mailService->sendVettedEmail($event->preferredLocale, $event->commonName, $event->email);
    }
}

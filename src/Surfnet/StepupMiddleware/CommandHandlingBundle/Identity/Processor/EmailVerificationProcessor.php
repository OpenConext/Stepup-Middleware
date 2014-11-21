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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Processor;

use Broadway\Processor\Processor;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorMailService;

class EmailVerificationProcessor extends Processor
{
    /**
     * @var SecondFactorMailService
     */
    private $service;

    /**
     * @param SecondFactorMailService $service
     */
    public function __construct(SecondFactorMailService $service)
    {
        $this->service = $service;
    }

    public function handlePhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->service->sendEmailVerificationEmail(
            'en_GB', // @TODO Identity preferred locale
            $event->identityId,
            $event->secondFactorId,
            $event->commonName,
            $event->email,
            $event->emailVerificationCode,
            $event->emailVerificationNonce
        );
    }

    public function handleYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->service->sendEmailVerificationEmail(
            'en_GB', // @TODO Identity preferred locale
            $event->identityId,
            $event->secondFactorId,
            $event->commonName,
            $event->email,
            $event->emailVerificationCode,
            $event->emailVerificationNonce
        );
    }
}

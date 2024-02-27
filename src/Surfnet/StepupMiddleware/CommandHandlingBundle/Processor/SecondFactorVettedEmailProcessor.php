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
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorVettedMailService;

final class SecondFactorVettedEmailProcessor extends Processor
{
    private SecondFactorVettedMailService $secondFactorVettedMailService;

    public function __construct(SecondFactorVettedMailService $secondFactorVettedMailService)
    {
        $this->secondFactorVettedMailService = $secondFactorVettedMailService;
    }

    public function handleSecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $this->secondFactorVettedMailService->sendVettedEmail($event->preferredLocale, $event->commonName, $event->email);
    }

    public function handleSecondFactorVettedWithoutTokenProofOfPossession(SecondFactorVettedWithoutTokenProofOfPossession $event): void
    {
        $this->secondFactorVettedMailService->sendVettedEmail($event->preferredLocale, $event->commonName, $event->email);
    }
}

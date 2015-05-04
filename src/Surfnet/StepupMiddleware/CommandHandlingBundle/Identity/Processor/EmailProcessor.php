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
use Surfnet\Stepup\IdentifyingData\Entity\IdentifyingDataRepository;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorMailService;

class EmailProcessor extends Processor
{
    /**
     * @var SecondFactorMailService
     */
    private $mailService;

    /**
     * @var RaService
     */
    private $raService;

    /**
     * @var IdentifyingDataRepository
     */
    private $identifyingDataRepository;

    /**
     * @param SecondFactorMailService   $mailService
     * @param RaService                 $raService
     * @param IdentifyingDataRepository $identifyingDataRepository
     */
    public function __construct(
        SecondFactorMailService $mailService,
        RaService $raService,
        IdentifyingDataRepository $identifyingDataRepository
    ) {
        $this->mailService = $mailService;
        $this->raService = $raService;
        $this->identifyingDataRepository = $identifyingDataRepository;
    }

    public function handlePhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $identifyingData = $this->getIdentifyingData($event->identifyingDataId);

        $this->mailService->sendEmailVerificationEmail(
            $event->preferredLocale,
            (string) $identifyingData->commonName,
            (string) $identifyingData->email,
            $event->emailVerificationNonce
        );
    }

    public function handleYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $identifyingData = $this->getIdentifyingData($event->identifyingDataId);

        $this->mailService->sendEmailVerificationEmail(
            $event->preferredLocale,
            (string) $identifyingData->commonName,
            (string) $identifyingData->email,
            $event->emailVerificationNonce
        );
    }

    public function handleGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $identifyingData = $this->getIdentifyingData($event->identifyingDataId);

        $this->mailService->sendEmailVerificationEmail(
            $event->preferredLocale,
            (string) $identifyingData->commonName,
            (string) $identifyingData->email,
            $event->emailVerificationNonce
        );
    }

    public function handleEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $identifyingData = $this->getIdentifyingData($event->identifyingDataId);

        $this->mailService->sendRegistrationEmail(
            $event->preferredLocale,
            (string) $identifyingData->commonName,
            (string) $identifyingData->email,
            $event->registrationCode,
            $this->raService->listRas($event->identityInstitution)
        );
    }

    /**
     * @param IdentifyingDataId $identifyingDataId
     * @return \Surfnet\Stepup\IdentifyingData\Entity\IdentifyingData
     */
    private function getIdentifyingData(IdentifyingDataId $identifyingDataId)
    {
        return $this->identifyingDataRepository->getById($identifyingDataId);
    }
}

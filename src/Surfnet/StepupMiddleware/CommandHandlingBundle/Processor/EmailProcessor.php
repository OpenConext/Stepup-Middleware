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
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\SecondFactorMailService;

class EmailProcessor extends Processor
{
    /**
     * @var SecondFactorMailService
     */
    private $mailService;

    /**
     * @var RaListingService
     */
    private $raListingService;

    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @var RaLocationService
     */
    private $raLocationsService;

    /**
     * @param SecondFactorMailService $mailService
     * @param RaListingService $raListingService
     * @param InstitutionConfigurationOptionsService $institutionConfigurationOptionsService
     * @param RaLocationService $raLocationsService
     */
    public function __construct(
        SecondFactorMailService $mailService,
        RaListingService $raListingService,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        RaLocationService $raLocationsService
    ) {
        $this->mailService                            = $mailService;
        $this->raListingService                       = $raListingService;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->raLocationsService                     = $raLocationsService;
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
        $institution = new Institution($event->identityInstitution->getInstitution());
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);

        if ($institutionConfigurationOptions->useRaLocationsOption->isEnabled()) {
            $this->sendRegistrationEmailWithRaLocations($event, $institution);

            return;
        }

        $ras = $this->raListingService->listRegistrationAuthoritiesFor($event->identityInstitution);

        if ($institutionConfigurationOptions->showRaaContactInformationOption->isEnabled()) {
            $this->sendRegistrationEmailWithRas($event, $ras);

            return;
        }

        $rasWithoutRaas = array_filter($ras, function (RegistrationAuthorityCredentials $ra) {
            return !$ra->isRaa();
        });

        $this->sendRegistrationEmailWithRas($event, $rasWithoutRaas);
    }

    public function handleSecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $this->mailService->sendVettedEmail($event->preferredLocale, $event->commonName, $event->email);
    }

    /**
     * @param EmailVerifiedEvent $event
     * @param $institution
     */
    private function sendRegistrationEmailWithRaLocations(EmailVerifiedEvent $event, $institution)
    {
        $this->mailService->sendRegistrationEmailWithRaLocations(
            (string)$event->preferredLocale,
            (string)$event->commonName,
            (string)$event->email,
            $event->registrationCode,
            $this->raLocationsService->listRaLocationsFor($institution)
        );
    }

    /**
     * @param EmailVerifiedEvent $event
     * @param RegistrationAuthorityCredentials[] $ras
     */
    private function sendRegistrationEmailWithRas(EmailVerifiedEvent $event, array $ras)
    {
        $this->mailService->sendRegistrationEmailWithRas(
            (string)$event->preferredLocale,
            (string)$event->commonName,
            (string)$event->email,
            $event->registrationCode,
            $ras
        );
    }
}

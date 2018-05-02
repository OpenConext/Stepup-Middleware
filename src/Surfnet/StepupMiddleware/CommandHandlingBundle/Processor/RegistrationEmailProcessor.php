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
use DateInterval;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PossessionProvenAndVerified;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RegistrationMailService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class RegistrationEmailProcessor extends Processor
{
    /**
     * @var RaLocationService
     */
    private $raLocationsService;

    /**
     * @var RegistrationMailService
     */
    private $registrationMailService;

    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @var RaListingService
     */
    private $raListingService;

    public function __construct(
        RegistrationMailService $registrationMailService,
        RaListingService $raListingService,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        RaLocationService $raLocationsService
    ) {
        $this->registrationMailService                = $registrationMailService;
        $this->raListingService                       = $raListingService;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->raLocationsService                     = $raLocationsService;
    }

    public function handlePhonePossessionProvenAndVerifiedEvent(PhonePossessionProvenAndVerifiedEvent $event)
    {
        $this->handlePossessionProvenAndVerifiedEvent($event);
    }

    public function handleYubikeyPossessionProvenAndVerifiedEvent(YubikeyPossessionProvenAndVerifiedEvent $event)
    {
        $this->handlePossessionProvenAndVerifiedEvent($event);
    }

    public function handleU2fDevicePossessionProvenAndVerifiedEvent(U2fDevicePossessionProvenAndVerifiedEvent $event)
    {
        $this->handlePossessionProvenAndVerifiedEvent($event);
    }

    public function handleGssfPossessionProvenAndVerifiedEvent(GssfPossessionProvenAndVerifiedEvent $event)
    {
        $this->handlePossessionProvenAndVerifiedEvent($event);
    }

    public function handleEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->handlePossessionProvenAndVerifiedEvent($event);
    }

    private function handlePossessionProvenAndVerifiedEvent(PossessionProvenAndVerified $event)
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

    /**
     * @param EmailVerifiedEvent $event
     * @param Institution $institution
     */
    private function sendRegistrationEmailWithRaLocations(PossessionProvenAndVerified $event, Institution $institution)
    {
        $this->registrationMailService->sendRegistrationEmailWithRaLocations(
            (string)$event->preferredLocale,
            (string)$event->commonName,
            (string)$event->email,
            $event->registrationCode,
            $this->getExpirationDateOfRegistration($event),
            $this->raLocationsService->listRaLocationsFor($institution)
        );
    }

    /**
     * @param PossessionProvenAndVerified $event
     * @param RegistrationAuthorityCredentials[] $ras
     */
    private function sendRegistrationEmailWithRas(PossessionProvenAndVerified $event, array $ras)
    {
        $this->registrationMailService->sendRegistrationEmailWithRas(
            (string)$event->preferredLocale,
            (string)$event->commonName,
            (string)$event->email,
            $event->registrationCode,
            $this->getExpirationDateOfRegistration($event),
            $ras
        );
    }

    /**
     * @param EmailVerifiedEvent $event
     * @return DateTime
     */
    private function getExpirationDateOfRegistration(PossessionProvenAndVerified $event)
    {
        return $event->registrationRequestedAt->add(
            new DateInterval('P14D')
        )->endOfDay();
    }
}

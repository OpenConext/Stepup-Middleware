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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Assert\Assertion;
use DateTime;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\VerifiedTokenInformation;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VerifiedSecondFactorReminderMailService
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private readonly string $fallbackLocale;

    public function __construct(
        private readonly Mailer $mailer,
        private readonly Sender $sender,
        TranslatorInterface $translator,
        private readonly EmailTemplateService $emailTemplateService,
        private readonly InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        private readonly RaListingService $raListingService,
        private readonly RaLocationService $raLocationService,
        string $fallbackLocale,
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');
        $this->translator = $translator;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * @return int
     */
    public function sendReminder(VerifiedTokenInformation $tokenInformation)
    {
        $institution = new Institution((string)$tokenInformation->getInstitution());
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);
        if ($institutionConfigurationOptions->useRaLocationsOption->isEnabled()) {
            return $this->sendReminderWithInstitution(
                $tokenInformation->getPreferredLocale(),
                $tokenInformation->getCommonName(),
                $tokenInformation->getEmail(),
                $tokenInformation->getRequestedAt(),
                $tokenInformation->getRegistrationCode(),
                $this->raLocationService->listRaLocationsFor($institution),
            );
        }

        $ras = $this->raListingService->listRegistrationAuthoritiesFor($tokenInformation->getInstitution());

        if ($institutionConfigurationOptions->showRaaContactInformationOption->isEnabled()) {
            return $this->sendReminderWithRas(
                $tokenInformation->getPreferredLocale(),
                $tokenInformation->getCommonName(),
                $tokenInformation->getEmail(),
                $tokenInformation->getRequestedAt(),
                $tokenInformation->getRegistrationCode(),
                $ras,
            );
        }

        $rasWithoutRaas = array_filter($ras, fn(RegistrationAuthorityCredentials $ra): bool => !$ra->isRaa());

        return $this->sendReminderWithRas(
            $tokenInformation->getPreferredLocale(),
            $tokenInformation->getCommonName(),
            $tokenInformation->getEmail(),
            $tokenInformation->getRequestedAt(),
            $tokenInformation->getRegistrationCode(),
            $rasWithoutRaas,
        );
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param DateTime $requestedAt
     * @param $registrationCode
     * @return void
     * @throws TransportExceptionInterface
     */
    private function sendReminderWithInstitution(
        $locale,
        $commonName,
        $email,
        $requestedAt,
        $registrationCode,
        $raLocations,
    ): void {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale,
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_verification_reminder_with_ra_locations',
            $locale,
            $this->fallbackLocale,
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale,
            'commonName' => $commonName,
            'expirationDate' => $requestedAt,
            'registrationCode' => $registrationCode,
            'raLocations' => $raLocations,
        ];

        $email = (new TemplatedEmail())
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email, $commonName))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($email);
    }

    private function sendReminderWithRas(
        $locale,
        $commonName,
        $email,
        $requestedAt,
        $registrationCode,
        array $ras,
    ): void {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale,
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_verification_reminder_with_ras',
            $locale,
            $this->fallbackLocale,
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale,
            'commonName' => $commonName,
            'expirationDate' => $requestedAt,
            'registrationCode' => $registrationCode,
            'ras' => $ras,
        ];

        $email = (new TemplatedEmail())
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email, $commonName))
            ->subject($subject)
            ->htmlTemplate('SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($email);
    }
}

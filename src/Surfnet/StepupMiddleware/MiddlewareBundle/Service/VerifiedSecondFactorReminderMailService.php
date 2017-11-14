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
use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class VerifiedSecondFactorReminderMailService
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var EmailTemplateService
     */
    private $emailTemplateService;

    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @var RaListingService
     */
    private $raListingService;

    /**
     * @var RaLocationService
     */
    private $raLocationService;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @param Mailer $mailer
     * @param Sender $sender
     * @param TranslatorInterface $translator
     * @param EngineInterface $templateEngine
     * @param EmailTemplateService $emailTemplateService
     * @param InstitutionConfigurationOptionsService $institutionConfigurationOptionsService
     * @param RaListingService $raListingService
     * @param RaLocationService $raLocationService
     * @param string $fallbackLocale
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        EmailTemplateService $emailTemplateService,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        RaListingService $raListingService,
        RaLocationService $raLocationService,
        $fallbackLocale
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->templateEngine = $templateEngine;
        $this->emailTemplateService = $emailTemplateService;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->raListingService = $raListingService;
        $this->raLocationService = $raLocationService;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * @param VerifiedTokenInformation $tokenInformation
     * @return int
     */
    public function sendReminder(VerifiedTokenInformation $tokenInformation)
    {
        $institution = new Institution((string) $tokenInformation->getInstitution());
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);
        if ($institutionConfigurationOptions->useRaLocationsOption->isEnabled()) {
            return $this->sendReminderWithInstitution(
                $tokenInformation->getPreferredLocale(),
                $tokenInformation->getCommonName(),
                $tokenInformation->getEmail(),
                $tokenInformation->getRequestedAt(),
                $tokenInformation->getRegistrationCode(),
                $this->raLocationService->listRaLocationsFor($institution)
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
                $ras
            );
        }

        $rasWithoutRaas = array_filter($ras, function (RegistrationAuthorityCredentials $ra) {
            return !$ra->isRaa();
        });

        return $this->sendReminderWithRas(
            $tokenInformation->getPreferredLocale(),
            $tokenInformation->getCommonName(),
            $tokenInformation->getEmail(),
            $tokenInformation->getRequestedAt(),
            $tokenInformation->getRegistrationCode(),
            $rasWithoutRaas
        );
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param DateTime $requestedAt
     * @param $registrationCode
     * @return int
     */
    private function sendReminderWithInstitution(
        $locale,
        $commonName,
        $email,
        $requestedAt,
        $registrationCode,
        $raLocations
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_verification_reminder_with_ra_locations',
            $locale,
            $this->fallbackLocale
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale,
            'commonName' => $commonName,
            'requestedAt' => $requestedAt,
            'registrationCode' => $registrationCode,
            'raLocations' => $raLocations,
        ];

        // Rendering file template instead of string
        // (https://github.com/symfony/symfony/issues/10865#issuecomment-42438248)
        $body = $this->templateEngine->render(
            'SurfnetStepupMiddlewareCommandHandlingBundle:SecondFactorMailService:email.html.twig',
            $parameters
        );

        /** @var Message $message */
        $message = $this->mailer->createMessage();
        $message
            ->setFrom($this->sender->getEmail(), $this->sender->getName())
            ->addTo($email, $commonName)
            ->setSubject($subject)
            ->setBody($body, 'text/html', 'utf-8');

        return $this->mailer->send($message);
    }

    private function sendReminderWithRas(
        $locale,
        $commonName,
        $email,
        $requestedAt,
        $registrationCode,
        array $ras
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_verification_reminder_with_ras',
            $locale,
            $this->fallbackLocale
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale,
            'commonName' => $commonName,
            'requestedAt' => $requestedAt,
            'registrationCode' => $registrationCode,
            'ras' => $ras,
        ];

        // Rendering file template instead of string
        // (https://github.com/symfony/symfony/issues/10865#issuecomment-42438248)
        $body = $this->templateEngine->render(
            'SurfnetStepupMiddlewareCommandHandlingBundle:SecondFactorMailService:email.html.twig',
            $parameters
        );

        /** @var Message $message */
        $message = $this->mailer->createMessage();
        $message
            ->setFrom($this->sender->getEmail(), $this->sender->getName())
            ->addTo($email, $commonName)
            ->setSubject($subject)
            ->setBody($body, 'text/html', 'utf-8');

        return $this->mailer->send($message);
    }
}

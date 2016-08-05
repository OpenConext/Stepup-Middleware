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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service;

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SecondFactorMailService
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
     * @var string
     */
    private $emailVerificationUrlTemplate;

    /**
     * @var \Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService
     */
    private $emailTemplateService;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Mailer $mailer
     * @param Sender $sender
     * @param TranslatorInterface $translator
     * @param EngineInterface $templateEngine
     * @param string $emailVerificationUrlTemplate
     * @param EmailTemplateService $emailTemplateService
     * @param string $fallbackLocale
     * @param LoggerInterface $logger
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        $emailVerificationUrlTemplate,
        EmailTemplateService $emailTemplateService,
        $fallbackLocale,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->templateEngine = $templateEngine;
        $this->emailVerificationUrlTemplate = $emailVerificationUrlTemplate;
        $this->emailTemplateService = $emailTemplateService;
        $this->fallbackLocale = $fallbackLocale;
        $this->logger = $logger;
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param string $verificationNonce
     */
    public function sendEmailVerificationEmail(
        $locale,
        $commonName,
        $email,
        $verificationNonce
    ) {
        $subject = $this->translator->trans(
            'ss.mail.email_verification_email.subject',
            ['%commonName%' => $commonName],
            null,
            $locale
        );

        $verificationUrl = str_replace(
            '{nonce}',
            urlencode($verificationNonce),
            $this->emailVerificationUrlTemplate
        );
        $emailTemplate = $this->emailTemplateService->findByName('confirm_email', $locale, $this->fallbackLocale);

        $parameters = [
            'templateString'   => $emailTemplate->htmlContent,
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'verificationUrl'  => $verificationUrl
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

        $this->mailer->send($message);
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param string $registrationCode
     * @param RegistrationAuthorityCredentials[] $ras
     */
    public function sendRegistrationEmailWithRas(
        $locale,
        $commonName,
        $email,
        $registrationCode,
        array $ras
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            null,
            $locale
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'registration_code_with_ras',
            $locale,
            $this->fallbackLocale
        );

        $parameters = [
            'templateString'   => $emailTemplate->htmlContent,
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'registrationCode' => $registrationCode,
            'ras'              => $ras,
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

        $this->mailer->send($message);
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param string $registrationCode
     * @param RaLocation[] $raLocations
     */
    public function sendRegistrationEmailWithRaLocations(
        $locale,
        $commonName,
        $email,
        $registrationCode,
        array $raLocations
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            null,
            $locale
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'registration_code_with_ra_locations',
            $locale,
            $this->fallbackLocale
        );

        $parameters = [
            'templateString'   => $emailTemplate->htmlContent,
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'registrationCode' => $registrationCode,
            'raLocations'      => $raLocations,
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

        $this->mailer->send($message);
    }

    /**
     * @param Locale     $locale
     * @param CommonName $commonName
     * @param Email      $email
     */
    public function sendVettedEmail(
        Locale $locale,
        CommonName $commonName,
        Email $email
    ) {
        $subject = $this->translator->trans(
            'ss.mail.vetted_email.subject',
            ['%commonName%' => $commonName->getCommonName(), '%email%' => $email->getEmail()],
            null,
            $locale->getLocale()
        );

        $emailTemplate = $this->emailTemplateService->findByName('vetted', $locale->getLocale(), $this->fallbackLocale);
        $parameters = [
            'templateString'   => $emailTemplate->htmlContent,
            'locale'           => $locale->getLocale(),
            'commonName'       => $commonName->getCommonName(),
            'email'            => $email->getEmail(),
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
            ->addTo($email->getEmail(), $commonName->getCommonName())
            ->setSubject($subject)
            ->setBody($body, 'text/html', 'utf-8');

        $this->mailer->send($message);
    }
}

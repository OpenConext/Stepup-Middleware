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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service;

use Assert\Assertion;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail as TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Translation\TranslatorInterface;

final class RegistrationMailService
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
     * @var \Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService
     */
    private $emailTemplateService;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @var string
     */
    private $selfServiceUrl;

    /**
     * @param Mailer $mailer
     * @param Sender $sender
     * @param TranslatorInterface $translator
     * @param EmailTemplateService $emailTemplateService
     * @param string $fallbackLocale
     * @param $selfServiceUrl
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EmailTemplateService $emailTemplateService,
        $fallbackLocale,
        $selfServiceUrl
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');

        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->emailTemplateService = $emailTemplateService;
        $this->fallbackLocale = $fallbackLocale;
        $this->selfServiceUrl = $selfServiceUrl;
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param string $registrationCode
     * @param DateTime $expirationDate
     * @param RegistrationAuthorityCredentials[] $ras
     * @throws TransportExceptionInterface
     */
    public function sendRegistrationEmailWithRas(
        $locale,
        $commonName,
        $email,
        $registrationCode,
        DateTime $expirationDate,
        array $ras
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
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
            'expirationDate'   => $expirationDate,
            'ras'              => $ras,
            'selfServiceUrl'   => $this->selfServiceUrl,
        ];

        $email = (new TemplatedEmail())
            ->from($this->sender->getEmail(), $this->sender->getName())
            ->to($email, $commonName)
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($email);
    }

    /**
     * @param string $locale
     * @param string $commonName
     * @param string $email
     * @param string $registrationCode
     * @param DateTime $expirationDate
     * @param RaLocation[] $raLocations
     * @throws TransportExceptionInterface
     */
    public function sendRegistrationEmailWithRaLocations(
        $locale,
        $commonName,
        $email,
        $registrationCode,
        DateTime $expirationDate,
        array $raLocations
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
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
            'expirationDate'   => $expirationDate,
            'raLocations'      => $raLocations,
            'selfServiceUrl'   => $this->selfServiceUrl,
        ];

        $email = (new TemplatedEmail())
            ->from($this->sender->getEmail(), $this->sender->getName())
            ->to($email, $commonName)
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($email);
    }
}

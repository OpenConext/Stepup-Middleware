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
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Translation\TranslatorInterface;

final class SecondFactorVettedMailService
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
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EmailTemplateService $emailTemplateService,
        string $fallbackLocale,
        string $selfServiceUrl
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');

        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->emailTemplateService = $emailTemplateService;
        $this->fallbackLocale = $fallbackLocale;
        $this->selfServiceUrl = $selfServiceUrl;
    }

    public function sendVettedEmail(
        Locale $locale,
        CommonName $commonName,
        Email $email
    ) {
        $subject = $this->translator->trans(
            'ss.mail.vetted_email.subject',
            ['%commonName%' => $commonName->getCommonName(), '%email%' => $email->getEmail()],
            'messages',
            $locale->getLocale()
        );

        $emailTemplate = $this->emailTemplateService->findByName('vetted', $locale->getLocale(), $this->fallbackLocale);

        // In TemplatedEmail email is a reserved keyword, we also use it as a parameter that can be used in the mail
        // message, to prevent having to update all templates, and prevent a 500 error from the mailer, we perform a
        // search and replace of the {email} parameter in the template.
        $emailTemplate->htmlContent = str_replace(
            '{email}',
            '{emailAddress}',
            $emailTemplate->htmlContent
        );
        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale->getLocale(),
            'commonName' => $commonName->getCommonName(),
            'selfServiceUrl' => $this->selfServiceUrl,
            'emailAddress' => $email->getEmail(),
        ];

        $message = new TemplatedEmail();
        $message
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email->getEmail(), $commonName->getCommonName()))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($message);
    }
}

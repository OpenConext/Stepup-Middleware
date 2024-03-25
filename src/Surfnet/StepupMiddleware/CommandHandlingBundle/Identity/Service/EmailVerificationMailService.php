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
use Assert\AssertionFailedException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailVerificationMailService
{
    private readonly string $emailVerificationUrlTemplate;

    /**
     * @throws AssertionFailedException
     */
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Sender $sender,
        private readonly TranslatorInterface $translator,
        string $emailVerificationUrlTemplate,
        private readonly EmailTemplateService $emailTemplateService,
        private readonly string $fallbackLocale,
        private readonly string $selfServiceUrl,
    ) {
        Assertion::string(
            $emailVerificationUrlTemplate,
            'Email verification URL template "%s" expected to be string, type %s given',
        );
        $this->emailVerificationUrlTemplate = $emailVerificationUrlTemplate;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailVerificationEmail(
        string $locale,
        string $commonName,
        string $email,
        string $verificationNonce,
    ): void {
        $subject = $this->translator->trans(
            'ss.mail.email_verification_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale,
        );

        $verificationUrl = str_replace(
            '{nonce}',
            urlencode($verificationNonce),
            $this->emailVerificationUrlTemplate,
        );

        // In TemplatedEmail email is a reserved keyword, we also use it as a parameter that can be used in the mail
        // message, to prevent having to update all templates, and prevent a 500 error from the mailer, we perform a
        // search and replace of the {email} parameter in the template.
        $emailTemplate = $this->emailTemplateService->findByName('confirm_email', $locale, $this->fallbackLocale);
        $emailTemplate->htmlContent = str_replace(
            '{email}',
            '{emailAddress}',
            $emailTemplate->htmlContent,
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale,
            'commonName' => $commonName,
            'emailAddress' => $email,
            'verificationUrl' => $verificationUrl,
            'selfServiceUrl' => $this->selfServiceUrl,
        ];

        $fromAddress = new Address($this->sender->getEmail(), $this->sender->getName());
        $toAddress = new Address($email, $commonName);
        $message = new TemplatedEmail();
        $message
            ->from($fromAddress)
            ->to($toAddress)
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($message);
    }
}

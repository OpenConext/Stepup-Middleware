<?php

/**
 * Copyright 2022 SURFnet B.V.
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
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use function str_replace;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecoveryTokenMailService
{
    private readonly string $fallbackLocale;

    public function __construct(
        private readonly Mailer $mailer,
        private readonly Sender $sender,
        private readonly TranslatorInterface $translator,
        private readonly EmailTemplateService $emailTemplateService,
        string $fallbackLocale,
        private readonly string $selfServiceUrl,
        private readonly LoggerInterface $logger,
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');
        $this->fallbackLocale = $fallbackLocale;
    }

    public function sendRevoked(
        Locale $locale,
        CommonName $commonName,
        Email $email,
        RecoveryTokenType $recoveryTokenType,
        RecoveryTokenId $tokenId,
        bool $revokedByRa,
    ): void {
        $this->logger->notice(
            sprintf('Sending a recovery token revoked mail message for token type %s', $recoveryTokenType),
        );

        $subjectParameters = [
            '%commonName%' => $commonName->getCommonName(),
            '%email%' => $email->getEmail(),
            '%tokenType%' => (string)$recoveryTokenType,
        ];

        $subject = $this->translator->trans(
            'ss.mail.recovery_token_revoked_email.subject',
            $subjectParameters,
            'messages',
            $locale->getLocale(),
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'recovery_token_revoked',
            $locale->getLocale(),
            $this->fallbackLocale,
        );

        // In TemplatedEmail email is a reserved keyword, we also use it as a parameter that can be used in the mail
        // message, to prevent having to update all templates, and prevent a 500 error from the mailer, we perform a
        // search and replace of the {email} parameter in the template.
        $emailTemplate->htmlContent = str_replace(
            '{email}',
            '{emailAddress}',
            $emailTemplate->htmlContent,
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale->getLocale(),
            'isRevokedByRa' => $revokedByRa,
            'tokenType' => (string)$recoveryTokenType,
            'tokenIdentifier' => (string)$tokenId,
            'selfServiceUrl' => $this->selfServiceUrl,
            'commonName' => $commonName->getCommonName(),
            'emailAddress' => $email->getEmail(),
        ];

        $message = new TemplatedEmail();
        $message
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email->getEmail(), $commonName->getCommonName()))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/RecoveryTokenMailService/email.html.twig')
            ->context($parameters);

        $this->mailer->send($message);
    }

    public function sendCreated(Locale $locale, CommonName $commonName, Email $email): void
    {
        $this->logger->notice('Sending a recovery token created mail message');

        $subjectParameters = [
            '%commonName%' => $commonName->getCommonName(),
            '%email%' => $email->getEmail(),
        ];

        $subject = $this->translator->trans(
            'ss.mail.recovery_token_created_email.subject',
            $subjectParameters,
            'messages',
            $locale->getLocale(),
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'recovery_token_created',
            $locale->getLocale(),
            $this->fallbackLocale,
        );

        // In TemplatedEmail email is a reserved keyword, we also use it as a parameter that can be used in the mail
        // message, to prevent having to update all templates, and prevent a 500 error from the mailer, we perform a
        // search and replace of the {email} parameter in the template.
        $emailTemplate->htmlContent = str_replace(
            '{email}',
            '{emailAddress}',
            $emailTemplate->htmlContent,
        );

        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale->getLocale(),
            'commonName' => $commonName->getCommonName(),
            'emailAddress' => $email->getEmail(),
        ];

        $message = (new TemplatedEmail())
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email->getEmail(), $commonName->getCommonName()))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/RecoveryTokenMailService/email.html.twig')
            ->context($parameters);

        $this->mailer->send($message);
    }
}

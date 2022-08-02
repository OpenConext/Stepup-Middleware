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
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

final class RecoveryTokenMailService
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
     * @var Environment
     */
    private $twig;

    /**
     * @var EmailTemplateService
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        Environment $twig,
        EmailTemplateService $emailTemplateService,
        string $fallbackLocale,
        string $selfServiceUrl,
        LoggerInterface $logger
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');

        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->twig = $twig;
        $this->emailTemplateService = $emailTemplateService;
        $this->fallbackLocale = $fallbackLocale;
        $this->selfServiceUrl = $selfServiceUrl;
        $this->logger = $logger;
    }

    public function sendRevoked(
        Locale $locale,
        CommonName $commonName,
        Email $email,
        RecoveryTokenType $recoveryTokenType,
        bool $revokedByRa
    ) {
        $subject = $this->translator->trans(
            'ss.mail.recovery_token_revoked_email.subject',
            ['%commonName%' => $commonName->getCommonName(), '%email%' => $email->getEmail()],
            'messages',
            $locale->getLocale()
        );

        $emailTemplate = $this->emailTemplateService->findByName('vetted', $locale->getLocale(), $this->fallbackLocale);
        $parameters = [
            'templateString' => $emailTemplate->htmlContent,
            'locale' => $locale->getLocale(),
            'revokedByRa' => $revokedByRa,
            'commonName' => $commonName->getCommonName(),
            'selfServiceUrl' => $this->selfServiceUrl,
            'email' => $email->getEmail(),
        ];

        $body = $this->twig->render(
            '@SurfnetStepupMiddlewareCommandHandling/RecoveryTokenMailService/email.html.twig',
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

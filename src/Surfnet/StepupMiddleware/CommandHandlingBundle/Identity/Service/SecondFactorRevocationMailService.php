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
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\SecondFactorDisplayNameResolverService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SecondFactorRevocationMailService
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
     * @var \Surfnet\StepupMiddleware\MiddlewareBundle\Service\SecondFactorDisplayNameResolverService
     */
    private $displayNameResolver;

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EmailTemplateService $emailTemplateService,
        string $fallbackLocale,
        string $selfServiceUrl,
        SecondFactorDisplayNameResolverService $displayNameResolver
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');
        Assertion::string($selfServiceUrl, 'Self Service URL "%s" expected to be string, type %s given');

        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->emailTemplateService = $emailTemplateService;
        $this->fallbackLocale = $fallbackLocale;
        $this->selfServiceUrl = $selfServiceUrl;
        $this->displayNameResolver = $displayNameResolver;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendVettedSecondFactorRevokedByRaEmail(
        Locale $locale,
        CommonName $commonName,
        Email $email,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier
    ) {
        $subject = $this->translator->trans(
            'mw.mail.second_factor_revoked.subject',
            [
                '%tokenType%' => $this->displayNameResolver->resolveByType($secondFactorType)
            ],
            'messages',
            $locale->getLocale()
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_revoked',
            $locale->getLocale(),
            $this->fallbackLocale
        );

        $parameters = [
            'isRevokedByRa' => true,
            'templateString' => $emailTemplate->htmlContent,
            'commonName' => $commonName->getCommonName(),
            'tokenType' => $this->displayNameResolver->resolveByType($secondFactorType),
            'tokenIdentifier' => $secondFactorIdentifier->getValue(),
            'selfServiceUrl' => $this->selfServiceUrl,
            'locale' => $locale->getLocale(),
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

    /**
     * @throws TransportExceptionInterface
     */
    public function sendVettedSecondFactorRevokedByRegistrantEmail(
        Locale $locale,
        CommonName $commonName,
        Email $email,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier
    ) {
        $subject = $this->translator->trans(
            'mw.mail.second_factor_revoked.subject',
            [
                '%tokenType%' => $this->displayNameResolver->resolveByType($secondFactorType)
            ],
            'messages',
            $locale->getLocale()
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'second_factor_revoked',
            $locale->getLocale(),
            $this->fallbackLocale
        );
        $parameters = [
            'isRevokedByRa' => false,
            'templateString' => $emailTemplate->htmlContent,
            'commonName' => $commonName->getCommonName(),
            'tokenType' => $this->displayNameResolver->resolveByType($secondFactorType),
            'tokenIdentifier' => $secondFactorIdentifier->getValue(),
            'selfServiceUrl' => $this->selfServiceUrl,
            'locale' => $locale->getLocale(),
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

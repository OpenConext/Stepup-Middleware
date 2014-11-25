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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SecondFactorMailService
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
    private $selfServiceEmailVerificationUrlTemplate;

    /**
     * @param Mailer $mailer
     * @param Sender $sender
     * @param TranslatorInterface $translator
     * @param EngineInterface $templateEngine
     * @param string $selfServiceEmailVerificationUrlTemplate
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        $selfServiceEmailVerificationUrlTemplate
    ) {
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->templateEngine = $templateEngine;
        $this->selfServiceEmailVerificationUrlTemplate = $selfServiceEmailVerificationUrlTemplate;
    }

    /**
     * @param string $locale
     * @param IdentityId $identityId
     * @param SecondFactorId $secondFactorId
     * @param string $commonName
     * @param string $email
     * @param string $verificationNonce
     */
    public function sendEmailVerificationEmail(
        $locale,
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        $commonName,
        $email,
        $verificationNonce
    ) {
        $subject = $this->translator->trans(
            'ss.mail.email_verification_email.subject',
            ['%commonName%' => $commonName]
        );

        $verificationUrl = str_replace(
            ['{identityId}', '{secondFactorId}', '{nonce}'],
            array_map('urlencode', [$identityId, $secondFactorId, $verificationNonce]),
            $this->selfServiceEmailVerificationUrlTemplate
        );
        $parameters = [
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'verificationUrl'  => $verificationUrl
        ];

        // Rendering file template instead of string
        // (https://github.com/symfony/symfony/issues/10865#issuecomment-42438248)
        $body = $this->templateEngine->render(
            'SurfnetStepupMiddlewareCommandHandlingBundle:SecondFactorMailService:emailVerificationEmail.html.twig',
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
     * @internal param SecondFactorId $secondFactorId
     * @internal param IdentityId $identityId
     */
    public function sendRegistrationEmail(
        $locale,
        $commonName,
        $email,
        $registrationCode
    ) {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName]
        );

        $parameters = [
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'registrationCode' => $registrationCode,
        ];

        // Rendering file template instead of string
        // (https://github.com/symfony/symfony/issues/10865#issuecomment-42438248)
        $body = $this->templateEngine->render(
            'SurfnetStepupMiddlewareCommandHandlingBundle:SecondFactorMailService:registrationEmail.html.twig',
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
}

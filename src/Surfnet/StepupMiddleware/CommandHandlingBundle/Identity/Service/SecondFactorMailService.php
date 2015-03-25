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

use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
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
    private $emailVerificationUrlTemplate;

    /**
     * @param Mailer $mailer
     * @param Sender $sender
     * @param TranslatorInterface $translator
     * @param EngineInterface $templateEngine
     * @param string $emailVerificationUrlTemplate
     */
    public function __construct(
        Mailer $mailer,
        Sender $sender,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        $emailVerificationUrlTemplate
    ) {
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->templateEngine = $templateEngine;
        $this->emailVerificationUrlTemplate = $emailVerificationUrlTemplate;
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
        $parameters = [
            'templateString'   => self::VERIFICATION_TEMPLATE,
            'locale'           => $locale,
            'commonName'       => $commonName,
            'email'            => $email,
            'verificationUrl'  => $verificationUrl
        ];

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
    public function sendRegistrationEmail(
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

        $parameters = [
            'templateString'   => self::REGISTRATION_TEMPLATE,
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

    const VERIFICATION_TEMPLATE = <<<TWIG
<p style="font-style:italic;text-align:center">- English version of this message below-</p>

<p>Beste {{ commonName }},</p>

<p>Bedankt voor het registreren van je token. Klik op onderstaande link om je e-mailadres te bevestigen:</p>
<p><a href="{{ verificationUrl }}">{{ verificationUrl }}</a></p>
<p>Is klikken op de link niet mogelijk? Kopieer dan de link en plak deze in de adresbalk van je browser.</p>
<p>SURFnet</p>

<hr>

<p>Dear {{ commonName }},</p>

<p>Thank you for registering your token. Please visit this link to verify your email address:</p>
<p><a href="{{ verificationUrl }}">{{ verificationUrl }}</a></p>
<p>If you can not click on the URL, please copy the link and paste it in the address bar of your browser.</p>
<p>SURFnet</p>
TWIG;

    const REGISTRATION_TEMPLATE = <<<TWIG
<p style="font-style:italic;text-align:center">- English version of this message below-</p>

<p>Beste {{ commonName }},</p>

<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>
<p>Neem aub het volgende mee:</p>
<ul>
    <li>Je token</li>
    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>
    <li>De registratiecode uit deze e-mail</li>
</ul>

<p style="font-size: 150%; text-align: center">
    <code>{{ registrationCode }}</code>
</p>

<p>Service Desk medewerkers die je token kunnen activeren:</p>

{% if ras is empty %}
    <p>Er zijn geen Service Desk medewerkers beschikbaar.</p>
{% else %}
    <ul>
        {% for ra in ras %}
            <li>
                <address>
                    <strong>{{ ra.commonName }}</strong><br>
                    {{ ra.location }}<br>
                    {{ ra.contactInformation }}
                </address>
            </li>
        {% endfor %}
    </ul>
{% endif %}

<hr>

<p>Dear {{ commonName }},</p>

<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>
<p>Please bring the following:</p>
<ul>
    <li>Your token</li>
    <li>A valid identity document (passport, drivers license or national ID-card.</li>
    <li>The registration code from this e-mail</li>
</ul>

<p style="font-size: 150%; text-align: center">
    <code>{{ registrationCode }}</code>
</p>

<p>Service Desk employees authorized to activate your token:</p>

{% if ras is empty %}
    <p>No Service Desk employees are available.</p>
{% else %}
    <ul>
        {% for ra in ras %}
            <li>
                <address>
                    <strong>{{ ra.commonName }}</strong><br>
                    {{ ra.location }}<br>
                    {{ ra.contactInformation }}
                </address>
            </li>
        {% endfor %}
    </ul>
{% endif %}
TWIG;
}

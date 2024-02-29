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
use DateInterval;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Sender;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface as Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegistrationMailService
{
    public $institutionConfigurationOptionsService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private readonly string $fallbackLocale;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Sender $sender,
        TranslatorInterface $translator,
        private readonly EmailTemplateService $emailTemplateService,
        string $fallbackLocale,
        private readonly string $selfServiceUrl,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        private readonly IdentityService $identityService,
        private readonly SecondFactorService $secondFactorService,
        private readonly RaLocationService $raLocationsService,
        private readonly RaListingService $raListingService,
        private readonly LoggerInterface $logger,
    ) {
        Assertion::string($fallbackLocale, 'Fallback locale "%s" expected to be string, type %s given');
        $this->translator = $translator;
        $this->fallbackLocale = $fallbackLocale;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
    }

    public function send(string $identityId, string $secondFactorId): void
    {
        $this->logger->notice(sprintf('Start processing of a registration email for %s', $identityId));
        $identity = $this->identityService->find($identityId);
        $institution = new Institution($identity->institution->getInstitution());
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);
        $verifiedSecondFactor = $this->secondFactorService->findVerified(new SecondFactorId($secondFactorId));

        if ($institutionConfigurationOptions->useRaLocationsOption->isEnabled()) {
            $this->logger->notice('Sending a registration mail with ra locations');

            $this->sendRegistrationEmailWithRaLocations(
                $identity->preferredLocale->getLocale(),
                $identity->commonName->getCommonName(),
                $identity->email->getEmail(),
                $verifiedSecondFactor->registrationCode,
                $this->getExpirationDateOfRegistration(
                    DateTime::fromString($verifiedSecondFactor->registrationRequestedAt->format(DateTime::FORMAT)),
                ),
                $this->raLocationsService->listRaLocationsFor($institution),
            );

            return;
        }

        $ras = $this->raListingService->listRegistrationAuthoritiesFor($identity->institution);
        if ($institutionConfigurationOptions->showRaaContactInformationOption->isEnabled()) {
            $this->logger->notice('Sending a registration mail with raa contact information');
            $this->sendRegistrationEmailWithRas(
                $identity->preferredLocale->getLocale(),
                $identity->commonName->getCommonName(),
                $identity->email->getEmail(),
                $verifiedSecondFactor->registrationCode,
                $this->getExpirationDateOfRegistration(
                    DateTime::fromString($verifiedSecondFactor->registrationRequestedAt->format(DateTime::FORMAT)),
                ),
                $ras,
            );
            return;
        }

        $rasWithoutRaas = array_filter($ras, fn(RegistrationAuthorityCredentials $ra): bool => !$ra->isRaa());
        $this->logger->notice(
            'Sending a registration mail with ra contact information as there are no RAAs at this location',
        );
        $this->sendRegistrationEmailWithRas(
            $identity->preferredLocale->getLocale(),
            $identity->commonName->getCommonName(),
            $identity->email->getEmail(),
            $verifiedSecondFactor->registrationCode,
            $this->getExpirationDateOfRegistration(
                DateTime::fromString($verifiedSecondFactor->registrationRequestedAt->format(DateTime::FORMAT)),
            ),
            $rasWithoutRaas,
        );
    }

    private function sendRegistrationEmailWithRas(
        string $locale,
        string $commonName,
        string $email,
        string $registrationCode,
        DateTime $expirationDate,
        array $ras,
    ): void {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale,
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'registration_code_with_ras',
            $locale,
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
            'locale' => $locale,
            'commonName' => $commonName,
            'emailAddress' => $email,
            'registrationCode' => $registrationCode,
            'expirationDate' => $expirationDate,
            'ras' => $ras,
            'selfServiceUrl' => $this->selfServiceUrl,
        ];

        $message = new TemplatedEmail();
        $message
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email, $commonName))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($message);
    }

    private function sendRegistrationEmailWithRaLocations(
        string $locale,
        string $commonName,
        string $email,
        string $registrationCode,
        DateTime $expirationDate,
        array $raLocations,
    ): void {
        $subject = $this->translator->trans(
            'ss.mail.registration_email.subject',
            ['%commonName%' => $commonName],
            'messages',
            $locale,
        );

        $emailTemplate = $this->emailTemplateService->findByName(
            'registration_code_with_ra_locations',
            $locale,
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
            'locale' => $locale,
            'commonName' => $commonName,
            'emailAddress' => $email,
            'registrationCode' => $registrationCode,
            'expirationDate' => $expirationDate,
            'raLocations' => $raLocations,
            'selfServiceUrl' => $this->selfServiceUrl,
        ];

        $message = new TemplatedEmail();
        $message
            ->from(new Address($this->sender->getEmail(), $this->sender->getName()))
            ->to(new Address($email, $commonName))
            ->subject($subject)
            ->htmlTemplate('@SurfnetStepupMiddlewareCommandHandling/SecondFactorMailService/email.html.twig')
            ->context($parameters);
        $this->mailer->send($message);
    }

    private function getExpirationDateOfRegistration(DateTime $date)
    {
        return $date->add(
            new DateInterval('P14D'),
        )->endOfDay();
    }
}

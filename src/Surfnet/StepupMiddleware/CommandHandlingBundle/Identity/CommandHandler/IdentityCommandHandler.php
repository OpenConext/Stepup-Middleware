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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\Repository\Repository as RepositoryInterface;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\SecondFactorNotAllowedException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\UnsupportedLocaleException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveU2fDevicePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SelfVetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\Exception\DuplicateIdentityException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IdentityCommandHandler extends SimpleCommandHandler
{
    /**
     * @var \Surfnet\Stepup\Identity\EventSourcing\IdentityRepository
     */
    private $eventSourcedRepository;

    /**
     * @var IdentityRepository
     */
    private $identityProjectionRepository;

    /**
     * @var \Surfnet\Stepup\Identity\Entity\ConfigurableSettings
     */
    private $configurableSettings;

    /**
     * @var AllowedSecondFactorListService
     */
    private $allowedSecondFactorListService;

    /** @var SecondFactorTypeService */
    private $secondFactorTypeService;

    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @var InstitutionConfigurationRepository
     */
    private $loaResolutionService;
    /**
     * @var SecondFactorProvePossessionHelper
     */
    private $provePossessionHelper;

    public function __construct(
        RepositoryInterface $eventSourcedRepository,
        IdentityRepository $identityProjectionRepository,
        ConfigurableSettings $configurableSettings,
        AllowedSecondFactorListService $allowedSecondFactorListService,
        SecondFactorTypeService $secondFactorTypeService,
        SecondFactorProvePossessionHelper $provePossessionHelper,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        LoaResolutionService $loaResolutionService
    ) {
        $this->eventSourcedRepository = $eventSourcedRepository;
        $this->identityProjectionRepository = $identityProjectionRepository;
        $this->configurableSettings = $configurableSettings;
        $this->allowedSecondFactorListService = $allowedSecondFactorListService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->provePossessionHelper = $provePossessionHelper;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->loaResolutionService = $loaResolutionService;
    }

    public function handleCreateIdentityCommand(CreateIdentityCommand $command)
    {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        $identity = Identity::create(
            new IdentityId($command->id),
            new Institution($command->institution),
            new NameId($command->nameId),
            new CommonName($command->commonName),
            new Email($command->email),
            $preferredLocale
        );

        $this->eventSourcedRepository->save($identity);
    }

    public function handleUpdateIdentityCommand(UpdateIdentityCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->id));

        $identity->rename(new CommonName($command->commonName));
        $identity->changeEmail(new Email($command->email));

        $this->eventSourcedRepository->save($identity);
    }

    public function handleBootstrapIdentityWithYubikeySecondFactorCommand(
        BootstrapIdentityWithYubikeySecondFactorCommand $command
    ) {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        $institution = new Institution($command->institution);
        $nameId = new NameId($command->nameId);

        if ($this->identityProjectionRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            throw DuplicateIdentityException::forBootstrappingWithYubikeySecondFactor($nameId, $institution);
        }

        $identity = Identity::create(
            new IdentityId($command->identityId),
            $institution,
            $nameId,
            new CommonName($command->commonName),
            new Email($command->email),
            $preferredLocale
        );

        $configurationInstitution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );

        $tokenCount = $this->institutionConfigurationOptionsService->getMaxNumberOfTokensFor($configurationInstitution);
        $identity->setMaxNumberOfTokens($tokenCount);

        $identity->bootstrapYubikeySecondFactor(
            new SecondFactorId($command->secondFactorId),
            new YubikeyPublicId($command->yubikeyPublicId)
        );

        $this->eventSourcedRepository->save($identity);
    }

    public function handleProveYubikeyPossessionCommand(ProveYubikeyPossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));

        $this->assertSecondFactorIsAllowedFor(new SecondFactorType('yubikey'), $identity->getInstitution());

        $configurationInstitution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );
        $tokenCount = $this->institutionConfigurationOptionsService->getMaxNumberOfTokensFor($configurationInstitution);
        $identity->setMaxNumberOfTokens($tokenCount);

        $identity->provePossessionOfYubikey(
            new SecondFactorId($command->secondFactorId),
            new YubikeyPublicId($command->yubikeyPublicId),
            $this->emailVerificationIsRequired($identity),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->eventSourcedRepository->save($identity);
    }

    /**
     * @param ProvePhonePossessionCommand $command
     */
    public function handleProvePhonePossessionCommand(ProvePhonePossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));

        $this->assertSecondFactorIsAllowedFor(new SecondFactorType('sms'), $identity->getInstitution());

        $configurationInstitution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );

        $tokenCount = $this->institutionConfigurationOptionsService->getMaxNumberOfTokensFor($configurationInstitution);
        $identity->setMaxNumberOfTokens($tokenCount);

        $identity->provePossessionOfPhone(
            new SecondFactorId($command->secondFactorId),
            new PhoneNumber($command->phoneNumber),
            $this->emailVerificationIsRequired($identity),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->eventSourcedRepository->save($identity);
    }

    /**
     * @param ProveGssfPossessionCommand $command
     */
    public function handleProveGssfPossessionCommand(ProveGssfPossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));
        $secondFactorType = $command->stepupProvider;

        // Validate that the chosen second factor type (stepupProvider) is allowed for the users instituti
        $this->assertSecondFactorIsAllowedFor(new SecondFactorType($secondFactorType), $identity->getInstitution());

        $configurationInstitution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );

        $tokenCount = $this->institutionConfigurationOptionsService->getMaxNumberOfTokensFor($configurationInstitution);
        $identity->setMaxNumberOfTokens($tokenCount);

        $identity->provePossessionOfGssf(
            new SecondFactorId($command->secondFactorId),
            new StepupProvider($secondFactorType),
            new GssfId($command->gssfId),
            $this->emailVerificationIsRequired($identity),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->eventSourcedRepository->save($identity);
    }

    public function handleProveU2fDevicePossessionCommand(ProveU2fDevicePossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));

        $this->assertSecondFactorIsAllowedFor(new SecondFactorType('u2f'), $identity->getInstitution());

        $configurationInstitution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );

        $tokenCount = $this->institutionConfigurationOptionsService->getMaxNumberOfTokensFor($configurationInstitution);
        $identity->setMaxNumberOfTokens($tokenCount);

        $identity->provePossessionOfU2fDevice(
            new SecondFactorId($command->secondFactorId),
            new U2fKeyHandle($command->keyHandle),
            $this->emailVerificationIsRequired($identity),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->eventSourcedRepository->save($identity);
    }

    /**
     * @param VerifyEmailCommand $command
     */
    public function handleVerifyEmailCommand(VerifyEmailCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));

        $identity->verifyEmail($command->verificationNonce);

        $this->eventSourcedRepository->save($identity);
    }

    public function handleVetSecondFactorCommand(VetSecondFactorCommand $command)
    {
        /** @var IdentityApi $authority */
        $authority = $this->eventSourcedRepository->load(new IdentityId($command->authorityId));
        /** @var IdentityApi $registrant */
        $registrant = $this->eventSourcedRepository->load(new IdentityId($command->identityId));

        $secondFactorType = new SecondFactorType($command->secondFactorType);
        $secondFactorIdentifier = SecondFactorIdentifierFactory::forType(
            $secondFactorType,
            $command->secondFactorIdentifier
        );

        $authority->vetSecondFactor(
            $registrant,
            new SecondFactorId($command->secondFactorId),
            $secondFactorType,
            $secondFactorIdentifier,
            $command->registrationCode,
            new DocumentNumber($command->documentNumber),
            $command->identityVerified,
            $this->secondFactorTypeService,
            $this->provePossessionHelper,
            $command->provePossessionSkipped
        );

        $this->eventSourcedRepository->save($authority);
        $this->eventSourcedRepository->save($registrant);
    }

    public function handleSelfVetSecondFactorCommand(SelfVetSecondFactorCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));
        $secondFactorIdentifier = SecondFactorIdentifierFactory::forType(
            new SecondFactorType($command->secondFactorType),
            $command->secondFactorId
        );
        $loa = $this->loaResolutionService->getLoa($command->authoringSecondFactorIdentifier);
        if ($loa === null) {
            throw new DomainException(
                sprintf(
                    'Authorizing second factor with LoA %s can not be resolved',
                    $command->authoringSecondFactorIdentifier
                )
            );
        }

        $identity->selfVetSecondFactor(
            $loa,
            $command->registrationCode,
            $secondFactorIdentifier,
            $this->secondFactorTypeService
        );
        $this->eventSourcedRepository->save($identity);
    }

    public function handleRevokeOwnSecondFactorCommand(RevokeOwnSecondFactorCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));
        $identity->revokeSecondFactor(new SecondFactorId($command->secondFactorId));

        $this->eventSourcedRepository->save($identity);
    }

    public function handleRevokeRegistrantsSecondFactorCommand(RevokeRegistrantsSecondFactorCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));
        $identity->complyWithSecondFactorRevocation(
            new SecondFactorId($command->secondFactorId),
            new IdentityId($command->authorityId)
        );

        $this->eventSourcedRepository->save($identity);
    }

    public function handleExpressLocalePreferenceCommand(ExpressLocalePreferenceCommand $command)
    {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        /** @var IdentityApi $identity */
        $identity = $this->eventSourcedRepository->load(new IdentityId($command->identityId));
        $identity->expressPreferredLocale($preferredLocale);

        $this->eventSourcedRepository->save($identity);
    }

    /**
     * @param Locale $locale
     */
    private function assertIsValidLocale(Locale $locale)
    {
        if (!$this->configurableSettings->isSupportedLocale($locale)) {
            throw new UnsupportedLocaleException(
                sprintf('Given locale "%s" is not a supported locale', (string) $locale)
            );
        }
    }

    private function assertSecondFactorIsAllowedFor(SecondFactorType $secondFactor, Institution $institution)
    {
        $allowedSecondFactorList = $this->allowedSecondFactorListService->getAllowedSecondFactorListFor(
            new ConfigurationInstitution($institution->getInstitution())
        );

        if (!$allowedSecondFactorList->allows($secondFactor)) {
            throw new SecondFactorNotAllowedException(sprintf(
                'Institution "%s" does not support second factor "%s"',
                $institution->getInstitution(),
                $secondFactor->getSecondFactorType()
            ));
        }
    }

    /**
     * @param IdentityApi $identity
     * @return bool
     */
    private function emailVerificationIsRequired(IdentityApi $identity)
    {
        $institution = new ConfigurationInstitution(
            (string) $identity->getInstitution()
        );

        $configuration = $this->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);

        if ($configuration === null) {
            return true;
        }

        return $configuration->verifyEmailOption->isEnabled();
    }
}

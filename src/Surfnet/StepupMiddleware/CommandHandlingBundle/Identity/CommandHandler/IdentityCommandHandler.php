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

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\RepositoryInterface;
use Surfnet\Stepup\IdentifyingData\Value\CommonName;
use Surfnet\Stepup\IdentifyingData\Value\Email;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\DomainException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class IdentityCommandHandler extends CommandHandler
{
    /**
     * @var \Surfnet\Stepup\Identity\EventSourcing\IdentityRepository
     */
    private $repository;

    /**
     * @var \Surfnet\Stepup\Identity\Entity\ConfigurableSettings
     */
    private $configurableSettings;

    /**
     * @param RepositoryInterface  $repository
     * @param ConfigurableSettings $configurableSettings
     */
    public function __construct(RepositoryInterface $repository, ConfigurableSettings $configurableSettings)
    {
        $this->repository           = $repository;
        $this->configurableSettings = $configurableSettings;
    }

    public function handleCreateIdentityCommand(CreateIdentityCommand $command)
    {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        $identity = Identity::create(
            new IdentityId($command->id),
            new Institution($command->institution),
            new NameId($command->nameId),
            new Email($command->email),
            new CommonName($command->commonName),
            $preferredLocale
        );

        $this->repository->save($identity);
    }

    public function handleUpdateIdentityCommand(UpdateIdentityCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load($command->id);

        $identity->rename(new CommonName($command->commonName));
        $identity->changeEmail(new Email($command->email));

        $this->repository->save($identity);
    }

    public function handleBootstrapIdentityWithYubikeySecondFactorCommand(
        BootstrapIdentityWithYubikeySecondFactorCommand $command
    ) {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        // @todo add check if Identity does not already exist based on NameId
        $identity = Identity::create(
            new IdentityId($command->identityId),
            new Institution($command->institution),
            new NameId($command->nameId),
            new Email($command->email),
            new CommonName($command->commonName),
            $preferredLocale
        );

        $identity->bootstrapYubikeySecondFactor(
            new SecondFactorId($command->secondFactorId),
            new YubikeyPublicId($command->yubikeyPublicId)
        );

        $this->repository->save($identity);
    }

    public function handleProveYubikeyPossessionCommand(ProveYubikeyPossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->provePossessionOfYubikey(
            new SecondFactorId($command->secondFactorId),
            new YubikeyPublicId($command->yubikeyPublicId),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->repository->save($identity);
    }

    /**
     * @param ProvePhonePossessionCommand $command
     */
    public function handleProvePhonePossessionCommand(ProvePhonePossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->provePossessionOfPhone(
            new SecondFactorId($command->secondFactorId),
            new PhoneNumber($command->phoneNumber),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->repository->save($identity);
    }

    /**
     * @param ProveGssfPossessionCommand $command
     */
    public function handleProveGssfPossessionCommand(ProveGssfPossessionCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->provePossessionOfGssf(
            new SecondFactorId($command->secondFactorId),
            new StepupProvider($command->stepupProvider),
            new GssfId($command->gssfId),
            $this->configurableSettings->createNewEmailVerificationWindow()
        );

        $this->repository->save($identity);
    }

    /**
     * @param VerifyEmailCommand $command
     */
    public function handleVerifyEmailCommand(VerifyEmailCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->verifyEmail($command->verificationNonce);

        $this->repository->save($identity);
    }

    public function handleVetSecondFactorCommand(VetSecondFactorCommand $command)
    {
        /** @var IdentityApi $authority */
        $authority = $this->repository->load(new IdentityId($command->authorityId));
        /** @var IdentityApi $registrant */
        $registrant = $this->repository->load(new IdentityId($command->identityId));

        $authority->vetSecondFactor(
            $registrant,
            new SecondFactorId($command->secondFactorId),
            $command->secondFactorIdentifier,
            $command->registrationCode,
            $command->documentNumber,
            $command->identityVerified
        );

        $this->repository->save($authority);
        $this->repository->save($registrant);
    }

    public function handleRevokeOwnSecondFactorCommand(RevokeOwnSecondFactorCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));
        $identity->revokeSecondFactor(new SecondFactorId($command->secondFactorId));

        $this->repository->save($identity);
    }

    public function handleRevokeRegistrantsSecondFactorCommand(RevokeRegistrantsSecondFactorCommand $command)
    {
        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));
        $identity->complyWithSecondFactorRevocation(
            new SecondFactorId($command->secondFactorId),
            new IdentityId($command->authorityId)
        );

        $this->repository->save($identity);
    }

    public function handleExpressLocalePreferenceCommand(ExpressLocalePreferenceCommand $command)
    {
        $preferredLocale = new Locale($command->preferredLocale);
        $this->assertIsValidLocale($preferredLocale);

        /** @var IdentityApi $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));
        $identity->expressPreferredLocale($preferredLocale);

        $this->repository->save($identity);
    }

    /**
     * @param Locale $locale
     */
    private function assertIsValidLocale(Locale $locale)
    {
        if (!$this->configurableSettings->isValidLocale($locale)) {
            throw new DomainException(
                sprintf('Given locale "%s" is not an acceptable locale', (string) $locale)
            );
        }
    }
}

<?php

/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventSourcing\MetadataEnricher;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\MigrateVettedSecondFactorCommand as CommandHandlingMigrateSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BootstrapCommandService
{
    /** @var Pipeline  */
    private $pipeline;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var MetadataEnricher */
    private $enricher;
    /** @var IdentityRepository  */
    private $identityRepository;
    /** @var UnverifiedSecondFactorRepository  */
    private $unverifiedSecondFactorRepository;
    /** @var VerifiedSecondFactorRepository */
    private $verifiedSecondFactorRepository;
    /** @var InstitutionConfigurationOptionsRepository */
    private $institutionConfigurationRepository;
    /** @var VettedSecondFactorRepository */
    private $vettedSecondFactorRepository;

    private $validRegistrationStatuses = ['unverified', 'verified', 'vetted'];

    public function __construct(
        Pipeline $pipeline,
        MetadataEnricher $enricher,
        TokenStorageInterface $tokenStorage,
        IdentityRepository $identityRepository,
        UnverifiedSecondFactorRepository $unverifiedSecondFactorRepository,
        VerifiedSecondFactorRepository $verifiedSecondFactorRepository,
        VettedSecondFactorRepository $vettedSecondFactorRepository,
        InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository
    ) {
        $this->pipeline = $pipeline;
        $this->enricher = $enricher;
        $this->tokenStorage = $tokenStorage;
        $this->identityRepository = $identityRepository;
        $this->unverifiedSecondFactorRepository = $unverifiedSecondFactorRepository;
        $this->verifiedSecondFactorRepository = $verifiedSecondFactorRepository;
        $this->institutionConfigurationRepository = $institutionConfigurationOptionsRepository;
        $this->vettedSecondFactorRepository = $vettedSecondFactorRepository;
    }

    public function setToken(TokenInterface $token)
    {
        $this->tokenStorage->setToken($token);
    }

    /**
     * @param string $registrationStatus
     */
    public function validRegistrationStatus($registrationStatus)
    {
        if (!in_array($registrationStatus, $this->validRegistrationStatuses)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid argument provided for the "registration-status" argument. One of: %s is expected. Received: "%s"',
                    implode(', ', $this->validRegistrationStatuses),
                    $registrationStatus
                )
            );
        }
    }

    public function requiresMailVerification(string $institution)
    {
        $configuration = $this->institutionConfigurationRepository->findConfigurationOptionsFor(new ConfigurationInstitution($institution));
        if ($configuration) {
            return $configuration->verifyEmailOption->isEnabled();
        }
        return true;
    }

    public function vetSecondFactor(
        string $tokenType,
        string $actorId,
        Identity $identity,
        string $secondFactorId,
        string $secondFactorIdentifier
    ) :void {
        $verifiedSecondFactor = $this->verifiedSecondFactorRepository->findOneBy(
            ['identityId' => $identity->id, 'type' => $tokenType]
        );

        $command = new VetSecondFactorCommand();
        $command->UUID = (string) Uuid::uuid4();
        $command->authorityId = $actorId;
        $command->identityId = $identity->id;
        $command->secondFactorId = $secondFactorId;
        $command->registrationCode = $verifiedSecondFactor->registrationCode;
        $command->secondFactorType = $tokenType;
        $command->secondFactorIdentifier = $secondFactorIdentifier;
        $command->documentNumber = '123987';
        $command->identityVerified = true;

        $this->pipeline->process($command);
    }

    /**
     * @param Institution $institution
     * @param NameId $nameId
     * @param $commonName
     * @param $email
     * @param $preferredLocale
     * @return CreateIdentityCommand
     */
    public function createIdentity(
        Institution $institution,
        NameId $nameId,
        $commonName,
        $email,
        $preferredLocale
    ) {
        $command = new CreateIdentityCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->id = (string)Uuid::uuid4();
        $command->institution = $institution->getInstitution();
        $command->nameId = $nameId->getNameId();
        $command->commonName = $commonName;
        $command->email = $email;
        $command->preferredLocale = $preferredLocale;

        $this->pipeline->process($command);

        return $command;
    }

    public function proveGsspPossession($secondFactorId, $identity, $tokenType, $tokenIdentifier)
    {
        $command = new ProveGssfPossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->stepupProvider = $tokenType;
        $command->gssfId = $tokenIdentifier;

        $this->pipeline->process($command);
    }

    public function provePhonePossession($secondFactorId, $identity, $phoneNumber)
    {
        $command = new ProvePhonePossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->phoneNumber = $phoneNumber;

        $this->pipeline->process($command);
    }

    public function proveYubikeyPossession($secondFactorId, $identity, $yubikeyPublicId)
    {
        $command = new ProveYubikeyPossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->yubikeyPublicId = $yubikeyPublicId;

        $this->pipeline->process($command);
    }

    public function verifyEmail(Identity $identity, string $tokenType)
    {
        $unverifiedSecondFactor = $this->unverifiedSecondFactorRepository->findOneBy(
            ['identityId' => $identity->id, 'type' => $tokenType]
        );

        $command = new VerifyEmailCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->identityId = $identity->id;
        $command->verificationNonce = $unverifiedSecondFactor->verificationNonce;

        $this->pipeline->process($command);
    }

    public function migrateVettedSecondFactor(Identity $sourceIdentity, Identity $targetIdentity, VettedSecondFactor $vettedSecondFactor)
    {
        $command = new CommandHandlingMigrateSecondFactorCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->sourceIdentityId = $sourceIdentity->id;
        $command->targetIdentityId = $targetIdentity->id;
        $command->sourceSecondFactorId = $vettedSecondFactor->id;
        $command->targetSecondFactorId = (string)Uuid::uuid4();

        $this->pipeline->process($command);
    }

    public function enrichEventMetadata($actorId)
    {
        $actor = $this->identityRepository->findOneBy(['id' => $actorId]);

        $metadata = new Metadata();
        $metadata->actorId = $actor->id;
        $metadata->actorInstitution = $actor->institution;
        $this->enricher->setMetadata($metadata);
    }

    /**
     * @return Identity
     */
    public function getIdentity(NameId $nameId, Institution $institution)
    {
        return $this->identityRepository->findOneByNameIdAndInstitution($nameId, $institution);
    }

    /**
     ** @return Identity
     */
    public function getIdentityByNameId(NameId $nameId)
    {
        return $this->identityRepository->findOneByNameId($nameId);
    }

    public function identityExists(NameId $nameId, Institution $institution)
    {
        return $this->identityRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution);
    }

    /**
     * @param Identity $identity
     * @return array|VettedSecondFactor[]
     */
    public function getVettedSecondFactorsFromIdentity(Identity $identity)
    {
        return $this->vettedSecondFactorRepository->findBy(['identityId' => $identity->id]);
    }
}

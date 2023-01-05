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
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository as RepositoryInterface;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AccreditIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AmendRegistrationAuthorityInformationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AppointRoleCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RetractRegistrationAuthorityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SaveVettingTypeHintCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\VettingTypeHintService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegistrationAuthorityCommandHandler extends SimpleCommandHandler
{
    /**
     * @var \Surfnet\Stepup\Identity\EventSourcing\IdentityRepository
     */
    private $repository;
    /**
     * @var InstitutionConfigurationRepository
     */
    private $institutionConfigurationRepository;

    /**
     * @var VettingTypeHintService;
     */
    private $vettingTypeHintService;

    public function __construct(
        RepositoryInterface $repository,
        InstitutionConfigurationRepository $institutionConfigurationRepository,
        VettingTypeHintService $hintService
    ) {
        $this->repository = $repository;
        $this->institutionConfigurationRepository = $institutionConfigurationRepository;
        $this->vettingTypeHintService = $hintService;
    }

    public function handleAccreditIdentityCommand(AccreditIdentityCommand $command)
    {
        /** @var \Surfnet\Stepup\Identity\Api\Identity $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $institutionConfiguration = $this->loadInstitutionConfigurationFor(new Institution($command->raInstitution));

        $role = $this->assertValidRoleAndConvertIfValid($command->role, $command->UUID);

        $identity->accreditWith(
            $role,
            new Institution($command->raInstitution),
            new Location($command->location),
            new ContactInformation($command->contactInformation),
            $institutionConfiguration
        );

        $this->repository->save($identity);
    }

    public function handleAmendRegistrationAuthorityInformationCommand(AmendRegistrationAuthorityInformationCommand $command)
    {
        /** @var \Surfnet\Stepup\Identity\Api\Identity $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->amendRegistrationAuthorityInformation(
            new Institution($command->raInstitution),
            new Location($command->location),
            new ContactInformation($command->contactInformation)
        );

        $this->repository->save($identity);
    }

    public function handleAppointRoleCommand(AppointRoleCommand $command)
    {
        /** @var \Surfnet\Stepup\Identity\Api\Identity $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $institutionConfiguration = $this->loadInstitutionConfigurationFor(new Institution($command->raInstitution));

        $newRole = $this->assertValidRoleAndConvertIfValid($command->role, $command->UUID);

        $identity->appointAs(new Institution($command->raInstitution), $newRole, $institutionConfiguration);

        $this->repository->save($identity);
    }

    public function handleRetractRegistrationAuthorityCommand(RetractRegistrationAuthorityCommand $command)
    {
        /** @var \Surfnet\Stepup\Identity\Api\Identity $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->retractRegistrationAuthority(new Institution($command->raInstitution));

        $this->repository->save($identity);
    }

    public function handleSaveVettingTypeHintCommand(SaveVettingTypeHintCommand $command)
    {
        $identity = $this->repository->load(new IdentityId($command->identityId));
        $collection = $this->vettingTypeHintService->collectionFrom($command->hints);
        $identity->saveVettingTypeHints(
            new Institution($command->institution),
            $collection
        );
        $this->repository->save($identity);
    }

    /**
     * @param string $role
     * @param string $commandId
     * @return RegistrationAuthorityRole
     */
    private function assertValidRoleAndConvertIfValid($role, $commandId)
    {
        if ($role === 'ra') {
            return new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA);
        } elseif ($role === 'raa') {
            return new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA);
        }

        throw new RuntimeException(sprintf(
            'Unknown role "%s" given by AccreditIdentityCommand "%s", must be "ra" or "raa"',
            $role,
            $commandId
        ));
    }

    /**
     * @deprecated Should be used until existing institution configurations have been migrated to using normalized ids
     *
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    private function loadInstitutionConfigurationFor(Institution $institution)
    {
        $institution = new ConfigurationInstitution($institution->getInstitution());
        try {
            $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
            $institutionConfiguration = $this->institutionConfigurationRepository->load(
                $institutionConfigurationId->getInstitutionConfigurationId()
            );
        } catch (AggregateNotFoundException $exception) {
            $institutionConfigurationId = InstitutionConfigurationId::from($institution);
            $institutionConfiguration = $this->institutionConfigurationRepository->load(
                $institutionConfigurationId->getInstitutionConfigurationId()
            );
        }

        return $institutionConfiguration;
    }
}

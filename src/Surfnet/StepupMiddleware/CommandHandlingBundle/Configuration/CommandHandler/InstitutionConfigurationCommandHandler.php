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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\CommandHandler;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository as RepositoryInterface;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\SsoRegistrationBypassOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveRaLocationCommand;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") Value objects
 */
class InstitutionConfigurationCommandHandler extends SimpleCommandHandler
{
    public function __construct(private readonly RepositoryInterface $repository)
    {
    }

    public function handleCreateInstitutionConfigurationCommand(CreateInstitutionConfigurationCommand $command): void
    {
        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);

        try {
            /** @var InstitutionConfiguration $institutionConfiguration */
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId(),
            );

            $institutionConfiguration->rebuild();
        } catch (AggregateNotFoundException) {
            $institutionConfiguration = InstitutionConfiguration::create($institutionConfigurationId, $institution);
        }

        $this->repository->save($institutionConfiguration);
    }

    public function handleReconfigureInstitutionConfigurationOptionsCommand(
        ReconfigureInstitutionConfigurationOptionsCommand $command,
    ): void {
        $institution = new Institution($command->institution);

        $allowedSecondFactors = array_map(
            fn($allowedSecondFactor): SecondFactorType => new SecondFactorType($allowedSecondFactor),
            $command->allowedSecondFactors,
        );


        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->configureUseRaLocationsOption(
            new UseRaLocationsOption($command->useRaLocationsOption),
        );
        $institutionConfiguration->configureVerifyEmailOption(
            new VerifyEmailOption($command->verifyEmailOption),
        );
        $institutionConfiguration->configureNumberOfTokensPerIdentityOption(
            new NumberOfTokensPerIdentityOption($command->numberOfTokensPerIdentityOption),
        );
        $institutionConfiguration->configureShowRaaContactInformationOption(
            new ShowRaaContactInformationOption($command->showRaaContactInformationOption),
        );

        // Configure the authorization options on the aggregate
        $institutionConfiguration->updateUseRaOption(
            InstitutionAuthorizationOption::fromInstitutionConfig(
                InstitutionRole::useRa(),
                $command->useRaOption,
            ),
        );
        $institutionConfiguration->updateUseRaaOption(
            InstitutionAuthorizationOption::fromInstitutionConfig(
                InstitutionRole::useRaa(),
                $command->useRaaOption,
            ),
        );
        $institutionConfiguration->updateSelectRaaOption(
            InstitutionAuthorizationOption::fromInstitutionConfig(
                InstitutionRole::selectRaa(),
                $command->selectRaaOption,
            ),
        );

        $institutionConfiguration->updateAllowedSecondFactorList(
            AllowedSecondFactorList::ofTypes($allowedSecondFactors),
        );

        // Handle optional options
        $selfVetOptionValue = $command->selfVetOption ?? SelfVetOption::getDefault()->isEnabled();
        $institutionConfiguration->configureSelfVetOption(new SelfVetOption($selfVetOptionValue));

        $ssoOn2faOptionValue = $command->ssoOn2faOption ?? SsoOn2faOption::getDefault()->isEnabled();
        $institutionConfiguration->configureSsoOn2faOption(new SsoOn2faOption($ssoOn2faOptionValue));

        $ssoRegistrationBypassOptionValue = $command->ssoRegistrationBypassOption ?? SsoRegistrationBypassOption::getDefault()->isEnabled();
        $institutionConfiguration->configureSsoRegistrationBypassOption(new SsoRegistrationBypassOption($ssoRegistrationBypassOptionValue));

        $satOption = $command->selfAssertedTokensOption ?? SelfAssertedTokensOption::getDefault()->isEnabled();
        $institutionConfiguration->configureSelfAssertedTokensOption(
            new SelfAssertedTokensOption($satOption),
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleAddRaLocationCommand(AddRaLocationCommand $command): void
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->addRaLocation(
            new RaLocationId($command->raLocationId),
            new RaLocationName($command->raLocationName),
            new Location($command->location),
            new ContactInformation($command->contactInformation),
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleChangeRaLocationCommand(ChangeRaLocationCommand $command): void
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->changeRaLocation(
            new RaLocationId($command->raLocationId),
            new RaLocationName($command->raLocationName),
            new Location($command->location),
            new ContactInformation($command->contactInformation),
        );

        $this->repository->save($institutionConfiguration);
    }

    public function handleRemoveRaLocationCommand(RemoveRaLocationCommand $command): void
    {
        $institution = new Institution($command->institution);

        $institutionConfiguration = $this->loadInstitutionConfigurationFor($institution);
        $institutionConfiguration->removeRaLocation(new RaLocationId($command->raLocationId));

        $this->repository->save($institutionConfiguration);
    }

    public function handleRemoveInstitutionConfigurationByUnnormalizedIdCommand(
        RemoveInstitutionConfigurationByUnnormalizedIdCommand $command,
    ): void {
        $institution = new Institution($command->institution);

        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        /** @var InstitutionConfiguration $institutionConfiguration */
        $institutionConfiguration = $this->repository->load(
            $institutionConfigurationId->getInstitutionConfigurationId(),
        );
        $institutionConfiguration->destroy();

        $this->repository->save($institutionConfiguration);
    }

    /**
     * @return InstitutionConfiguration
     * @deprecated Should be used until existing institution configurations have been migrated to using normalized ids
     *
     */
    private function loadInstitutionConfigurationFor(Institution $institution): InstitutionConfiguration
    {
        try {
            $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
            /** @var InstitutionConfiguration $institutionConfiguration */
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId(),
            );
        } catch (AggregateNotFoundException) {
            $institutionConfigurationId = InstitutionConfigurationId::from($institution);
            /** @var InstitutionConfiguration $institutionConfiguration */
            $institutionConfiguration = $this->repository->load(
                $institutionConfigurationId->getInstitutionConfigurationId(),
            );
        }

        return $institutionConfiguration;
    }
}

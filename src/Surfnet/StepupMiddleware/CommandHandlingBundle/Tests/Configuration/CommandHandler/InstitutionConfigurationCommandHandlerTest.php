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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Configuration\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Broadway\Repository\AggregateNotFoundException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SelfVetOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
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
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\CommandHandler\InstitutionConfigurationCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

class InstitutionConfigurationCommandHandlerTest extends CommandHandlerTest
{

    /**
     * @test
     * @group command-handler
     */
    public function an_institution_configuration_is_created_when_there_is_none_for_a_given_institution(): void
    {
        $command = new CreateInstitutionConfigurationCommand();
        $command->institution = 'An institution';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $defaultUseRaLocationsOption = UseRaLocationsOption::getDefault();
        $defaultShowRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $defaultVerifyEmailOption = VerifyEmailOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $defaultAllowedSecondFactorList = AllowedSecondFactorList::blank();
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->when($command)
            ->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultUseRaLocationsOption,
                    $defaultShowRaaContactInformationOption,
                    $defaultVerifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_institution_configuration_cannot_be_created_when_there_already_is_one_for_a_given_institution(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot rebuild InstitutionConfiguration as it has not been destroyed');

        $command = new CreateInstitutionConfigurationCommand();
        $command->institution = 'An institution';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function institution_configuration_options_are_not_changed_if_their_given_value_is_not_different_from_their_current_value(): void
    {
        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(1);
        $defaultAllowedSecondFactorList = AllowedSecondFactorList::blank();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $selfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->useRaOption = $useRaOption->jsonSerialize();
        $command->useRaaOption = $useRaaOption->jsonSerialize();
        $command->selectRaaOption = $selectRaaOption->jsonSerialize();
        $command->allowedSecondFactors = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function use_ra_locations_option_is_changed_if_its_given_value_is_different_from_the_current_value(): void
    {
        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();

        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $defaultAllowedSecondFactorList = AllowedSecondFactorList::blank();

        $differentUseRaLocationsOptionValue = true;

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $differentUseRaLocationsOptionValue;
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $selfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->useRaOption = $useRaOption->jsonSerialize();
        $command->useRaaOption = $useRaaOption->jsonSerialize();
        $command->selectRaaOption = $selectRaaOption->jsonSerialize();
        $command->allowedSecondFactors = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ])
            ->when($command)
            ->then([
                new UseRaLocationsOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new UseRaLocationsOption($differentUseRaLocationsOptionValue),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function show_raa_contact_information_option_is_changed_if_its_given_value_is_different_from_the_current_value(): void
    {
        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $defaultAllowedSecondFactorList = AllowedSecondFactorList::blank();

        $differentShowRaaContactInformationOptionValue = false;

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->showRaaContactInformationOption = $differentShowRaaContactInformationOptionValue;
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $selfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->allowedSecondFactors = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ])
            ->when($command)
            ->then([
                new ShowRaaContactInformationOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new ShowRaaContactInformationOption($differentShowRaaContactInformationOptionValue),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function allowed_second_factor_list_is_changed_if_its_values_are_different_than_the_current_list(): void
    {
        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();

        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $originalAllowedSecondFactorList = AllowedSecondFactorList::blank();

        $secondFactorsToAllow = ['sms', 'yubikey'];
        $updatedAllowedSecondFactorList = AllowedSecondFactorList::ofTypes([
            new SecondFactorType($secondFactorsToAllow[0]),
            new SecondFactorType($secondFactorsToAllow[1]),
        ]);

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $selfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->allowedSecondFactors = $secondFactorsToAllow;

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalAllowedSecondFactorList,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
            ])
            ->when($command)
            ->then([
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $updatedAllowedSecondFactorList,
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function allowed_second_factor_list_is_not_changed_if_its_values_are_the_same_as_the_current_list(): void
    {
        $secondFactorsToAllow = ['sms', 'yubikey'];
        $allowedSecondFactorList = AllowedSecondFactorList::ofTypes([
            new SecondFactorType($secondFactorsToAllow[0]),
            new SecondFactorType($secondFactorsToAllow[1]),
        ]);

        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $originalAllowedSecondFactorList = $allowedSecondFactorList;

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $selfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->allowedSecondFactors = $secondFactorsToAllow;

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalAllowedSecondFactorList,
                ),
            ])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_added_to_an_existing_institution_configuration(): void
    {
        $command = new AddRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function the_same_ra_location_cannot_be_added_twice(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already present');

        $command = new AddRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_renamed(): void
    {
        $originalRaLocationName = new RaLocationName('An old RA location name');

        $command = new ChangeRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    $originalRaLocationName,
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationRenamedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_it_is_not_present_within_an_institution_configuration(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not present');

        $command = new ChangeRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_its_institution_configuration_cannot_be_found(): void
    {
        $this->expectException(AggregateNotFoundException::class);
        $this->expectExceptionMessage('not found');

        $command = new ChangeRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId(self::uuid())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_can_be_relocated(): void
    {
        $originalLocation = new Location('An old location');

        $command = new ChangeRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    $originalLocation,
                    new ContactInformation($command->contactInformation),
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationRelocatedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new Location($command->location),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_locations_contact_information_can_be_changed(): void
    {
        $originalContactInformation = new ContactInformation('Old contact information');

        $command = new ChangeRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';
        $command->raLocationName = 'An RA location name';
        $command->location = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    $originalContactInformation,
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationContactInformationChangedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new ContactInformation($command->contactInformation),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function the_self_vet_option_can_be_changed(): void
    {
        $institution = new Institution('Institution');
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption = new VerifyEmailOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $newSelfVetOption = new SelfVetOption(true);
        $useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());
        $defaultAllowedSecondFactorList = AllowedSecondFactorList::blank();

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption = $verifyEmailOption->isEnabled();
        $command->selfVetOption = $newSelfVetOption->isEnabled();
        $command->selfAssertedTokensOption = $selfAssertedTokensOption->isEnabled();
        $command->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        $command->allowedSecondFactors = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),

            ])
            ->when($command)
            ->then([
                new UseRaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaOption,
                ),
                new UseRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaaOption,
                ),
                new SelectRaaOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $selectRaaOption,
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList,
                ),
                new SelfVetOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $newSelfVetOption,
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_cannot_be_removed_if_its_institution_configuration_cannot_be_found(): void
    {
        $this->expectException(AggregateNotFoundException::class);
        $this->expectExceptionMessage('not found');

        $command = new RemoveRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId(self::uuid())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_cannot_be_removed_if_it_is_not_present_within_an_institution_configuration(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not present');

        $command = new RemoveRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_can_be_removed(): void
    {
        $command = new RemoveRaLocationCommand();
        $command->raLocationId = self::uuid();
        $command->institution = 'An institution';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption,
                    $numberOfTokensPerIdentityOption,
                    $ssoOn2faOption,
                    $ssoRegistrationBypassOption,
                    $selfVetOption,
                    $selfAssertedTokensOption,
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName('A location name'),
                    new Location('A location'),
                    new ContactInformation('Some contact information'),
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationRemovedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_institution_configuration_with_unnormalized_institution_configuration_id_can_be_removed(): void
    {
        $command = new RemoveInstitutionConfigurationByUnnormalizedIdCommand();
        $command->institution = 'Babelfish Inc.';

        $institution = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption = new VerifyEmailOption(true);
        $ssoOn2faOption = SsoOn2faOption::getDefault();
        $ssoRegistrationBypassOption = SsoRegistrationBypassOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $selfAssertedTokensOption = SelfAssertedTokensOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given(
                [
                    new NewInstitutionConfigurationCreatedEvent(
                        $institutionConfigurationId,
                        $institution,
                        $useRaLocationsOption,
                        $showRaaContactInformationOption,
                        $verifyEmailOption,
                        $numberOfTokensPerIdentityOption,
                        $ssoOn2faOption,
                        $ssoRegistrationBypassOption,
                        $selfVetOption,
                        $selfAssertedTokensOption,
                    ),
                ],
            )
            ->when($command)
            ->then(
                [
                    new InstitutionConfigurationRemovedEvent(
                        $institutionConfigurationId,
                        $institution,
                    ),
                ],
            );
    }

    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     *
     * @return CommandHandler
     */
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new InstitutionConfigurationCommandHandler(
            new InstitutionConfigurationRepository($eventStore, $eventBus, $aggregateFactory),
        );
    }
}

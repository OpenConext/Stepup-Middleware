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

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
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
    public function an_institution_configuration_is_created_when_there_is_none_for_a_given_institution()
    {
        $command              = new CreateInstitutionConfigurationCommand();
        $command->institution = 'An institution';

        $institution                            = new Institution($command->institution);
        $institutionConfigurationId             = InstitutionConfigurationId::normalizedFrom($institution);
        $defaultUseRaLocationsOption            = UseRaLocationsOption::getDefault();
        $defaultShowRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $defaultVerifyEmailOption               = VerifyEmailOption::getDefault();
        $defaultAllowedSecondFactorList         = AllowedSecondFactorList::blank();

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->when($command)
            ->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultUseRaLocationsOption,
                    $defaultShowRaaContactInformationOption,
                    $defaultVerifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_institution_configuration_cannot_be_created_when_there_already_is_one_for_a_given_institution()
    {
        $this->setExpectedException(
            'Surfnet\Stepup\Exception\DomainException',
            'Cannot rebuild InstitutionConfiguration as it has not been destroyed'
        );

        $command                     = new CreateInstitutionConfigurationCommand();
        $command->institution        = 'An institution';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function institution_configuration_options_are_not_changed_if_their_given_value_is_not_different_from_their_current_value()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $defaultAllowedSecondFactorList  = AllowedSecondFactorList::blank();

        $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution                     = $institution->getInstitution();
        $command->useRaLocationsOption            = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption               = $verifyEmailOption->isEnabled();
        $command->allowedSecondFactors            = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList
                )
            ])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function use_ra_locations_option_is_changed_if_its_given_value_is_different_from_the_current_value()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $defaultAllowedSecondFactorList  = AllowedSecondFactorList::blank();

        $differentUseRaLocationsOptionValue = true;

        $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution                     = $institution->getInstitution();
        $command->useRaLocationsOption            = $differentUseRaLocationsOptionValue;
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption               = $verifyEmailOption->isEnabled();
        $command->allowedSecondFactors            = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList
                )
            ])
            ->when($command)
            ->then([
                new UseRaLocationsOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new UseRaLocationsOption($differentUseRaLocationsOptionValue)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function show_raa_contact_information_option_is_changed_if_its_given_value_is_different_from_the_current_value()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $defaultAllowedSecondFactorList  = AllowedSecondFactorList::blank();

        $differentShowRaaContactInformationOptionValue = false;

        $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution                     = $institution->getInstitution();
        $command->showRaaContactInformationOption = $differentShowRaaContactInformationOptionValue;
        $command->useRaLocationsOption            = $useRaLocationsOption->isEnabled();
        $command->verifyEmailOption               = $verifyEmailOption->isEnabled();
        $command->allowedSecondFactors            = [];

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $defaultAllowedSecondFactorList
                )
            ])
            ->when($command)
            ->then([
                new ShowRaaContactInformationOptionChangedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new ShowRaaContactInformationOption($differentShowRaaContactInformationOptionValue)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function allowed_second_factor_list_is_changed_if_its_values_are_different_than_the_current_list()
    {
        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption               = new VerifyEmailOption(true);
        $originalAllowedSecondFactorList = AllowedSecondFactorList::blank();

        $secondFactorsToAllow = ['sms', 'yubikey'];
        $updatedAllowedSecondFactorList = AllowedSecondFactorList::ofTypes([
            new SecondFactorType($secondFactorsToAllow[0]),
            new SecondFactorType($secondFactorsToAllow[1])
        ]);

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption               = $verifyEmailOption->isEnabled();
        $command->allowedSecondFactors = $secondFactorsToAllow;

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalAllowedSecondFactorList
                )
            ])
            ->when($command)
            ->then([
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $updatedAllowedSecondFactorList
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function allowed_second_factor_list_is_not_changed_if_its_values_are_the_same_as_the_current_list()
    {
        $secondFactorsToAllow = ['sms', 'yubikey'];
        $allowedSecondFactorList = AllowedSecondFactorList::ofTypes([
            new SecondFactorType($secondFactorsToAllow[0]),
            new SecondFactorType($secondFactorsToAllow[1])
        ]);

        $institution                     = new Institution('Institution');
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption               = new VerifyEmailOption(true);
        $originalAllowedSecondFactorList = $allowedSecondFactorList;

        $command = new ReconfigureInstitutionConfigurationOptionsCommand();
        $command->institution = $institution->getInstitution();
        $command->useRaLocationsOption = $useRaLocationsOption->isEnabled();
        $command->showRaaContactInformationOption = $showRaaContactInformationOption->isEnabled();
        $command->verifyEmailOption               = $verifyEmailOption->isEnabled();
        $command->allowedSecondFactors = $secondFactorsToAllow;

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new AllowedSecondFactorListUpdatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $originalAllowedSecondFactorList
                )
            ])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_added_to_an_existing_institution_configuration()
    {
        $command                     = new AddRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
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
                    new ContactInformation($command->contactInformation)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function the_same_ra_location_cannot_be_added_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'already present');

        $command                     = new AddRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_renamed()
    {
        $originalRaLocationName = new RaLocationName('An old RA location name');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    $originalRaLocationName,
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command)
            ->then([
                new RaLocationRenamedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_it_is_not_present_within_an_institution_configuration()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'not present');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_its_institution_configuration_cannot_be_found()
    {
        $this->setExpectedException('Broadway\Repository\AggregateNotFoundException', 'not found');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId(self::uuid())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_can_be_relocated()
    {
        $originalLocation= new Location('An old location');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    $originalLocation,
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command)
            ->then([
                new RaLocationRelocatedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new Location($command->location)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_locations_contact_information_can_be_changed()
    {
        $originalContactInformation= new ContactInformation('Old contact information');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    $originalContactInformation
                )
            ])
            ->when($command)
            ->then([
                new RaLocationContactInformationChangedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new ContactInformation($command->contactInformation)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_cannot_be_removed_if_its_institution_configuration_cannot_be_found()
    {
        $this->setExpectedException('Broadway\Repository\AggregateNotFoundException', 'not found');

        $command                     = new RemoveRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId(self::uuid())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_cannot_be_removed_if_it_is_not_present_within_an_institution_configuration()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'not present');

        $command                     = new RemoveRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_can_be_removed()
    {
        $command                     = new RemoveRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::normalizedFrom($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution,
                    $useRaLocationsOption,
                    $showRaaContactInformationOption,
                    $verifyEmailOption
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    $institution,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName('A location name'),
                    new Location('A location'),
                    new ContactInformation('Some contact information')
                )
            ])
            ->when($command)
            ->then([
                new RaLocationRemovedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_institution_configuration_with_unnormalized_institution_configuration_id_can_be_removed()
    {
        $command               = new RemoveInstitutionConfigurationByUnnormalizedIdCommand();
        $command->institution  = 'Babelfish Inc.';

        $institution                     = new Institution($command->institution);
        $institutionConfigurationId      = InstitutionConfigurationId::from($institution);
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $verifyEmailOption               = new VerifyEmailOption(true);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given(
                [
                    new NewInstitutionConfigurationCreatedEvent(
                        $institutionConfigurationId,
                        $institution,
                        $useRaLocationsOption,
                        $showRaaContactInformationOption,
                        $verifyEmailOption
                    )
                ]
            )
            ->when($command)
            ->then(
                [
                    new InstitutionConfigurationRemovedEvent(
                        $institutionConfigurationId,
                        $institution
                    )
                ]
            );
    }

    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     *
     * @return CommandHandlerInterface
     */
    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new InstitutionConfigurationCommandHandler(
            new InstitutionConfigurationRepository($eventStore, $eventBus, $aggregateFactory)
        );
    }
}

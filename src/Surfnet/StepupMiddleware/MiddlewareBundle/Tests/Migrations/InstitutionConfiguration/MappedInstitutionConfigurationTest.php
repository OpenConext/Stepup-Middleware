<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Migrations\InstitutionConfiguration;

use PHPUnit\Framework\TestCase as UnitTest;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Migrations\InstitutionConfiguration\MappedInstitutionConfiguration;

class MappedInstitutionConfigurationTest extends UnitTest
{
    /**
     * @test
     * @group middleware-migration
     */
    public function infers_the_correct_remove_institution_configuration_command()
    {
        $institution = new Institution('Babelfish Inc.');
        $useRaLocationsOption = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption = VerifyEmailOption::getDefault();
        $numberOfTokensPerIdentityOption = NumberOfTokensPerIdentityOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $raLocations = [];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $command = $mapped->inferRemoveInstitutionConfigurationByIdCommand();

        $this->assertInstanceOf(RemoveInstitutionConfigurationByUnnormalizedIdCommand::class, $command);
        $this->assertEquals($institution->getInstitution(), $command->institution);
    }

    /**
     * @test
     * @group middleware-migration
     */
    public function infers_the_correct_create_institution_configuration_command()
    {
        $institution = new Institution('Babelfish Inc.');
        $useRaLocationsOption = UseRaLocationsOption::getDefault();
        $showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $verifyEmailOption = VerifyEmailOption::getDefault();
        $selfVetOption = SelfVetOption::getDefault();
        $numberOfTokensPerIdentityOption = NumberOfTokensPerIdentityOption::getDefault();
        $raLocations = [];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $command = $mapped->inferCreateInstitutionConfigurationCommand();

        $this->assertInstanceOf(CreateInstitutionConfigurationCommand::class, $command);
        $this->assertEquals($institution->getInstitution(), $command->institution);
    }

    /**
     * @test
     * @group middleware-migration
     */
    public function infers_the_correct_reconfigure_institution_configuration_command()
    {
        $institution = new Institution('Babelfish Inc.');
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(false);
        $verifyEmailOption = new VerifyEmailOption(true);
        $selfVetOption = new SelfVetOption(true);
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(2);
        $raLocations = [];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $command = $mapped->inferReconfigureInstitutionConfigurationCommand();

        $this->assertInstanceOf(ReconfigureInstitutionConfigurationOptionsCommand::class, $command);
        $this->assertEquals($institution->getInstitution(), $command->institution);
        $this->assertEquals($useRaLocationsOption->isEnabled(), $command->useRaLocationsOption);
        $this->assertEquals($showRaaContactInformationOption->isEnabled(), $command->showRaaContactInformationOption);
        $this->assertEquals($verifyEmailOption->isEnabled(), $command->verifyEmailOption);
        $this->assertEquals($selfVetOption->isEnabled(), $command->selfVetOption);
    }

    /**
     * @test
     * @group middleware-migration
     */
    public function no_ra_locations_means_no_add_ra_location_command()
    {
        $institution                     = new Institution('Babelfish Inc.');
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(false);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $selfVetOption = SelfVetOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(1);
        $raLocations                     = [];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $commands = $mapped->inferAddRaLocationCommands();

        $this->assertEquals(0, count($commands));
    }

    /**
     * @test
     * @group middleware-migration
     */
    public function a_single_ra_location_means_a_single_correct_add_ra_location_command()
    {
        $institution                     = new Institution('Babelfish Inc.');
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(false);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $selfVetOption = SelfVetOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(0);
        $raLocation                      = RaLocation::create(
            (string) Uuid::uuid4(),
            $institution,
            new RaLocationName('Some Location'),
            new Location('Somewhere here or there'),
            new ContactInformation('Per phone.')
        );
        $raLocations                     = [$raLocation];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $commands = $mapped->inferAddRaLocationCommands();

        $this->assertEquals(1, count($commands));

        $command = reset($commands);
        $this->assertCommandMatches($command, $institution, $raLocation);
    }

    /**
     * @test
     * @group middleware-migration
     */
    public function multiple_ra_locations_mean_multiple_correct_add_ra_location_commands()
    {
        $institution                     = new Institution('Babelfish Inc.');
        $useRaLocationsOption            = new UseRaLocationsOption(true);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(false);
        $verifyEmailOption               = new VerifyEmailOption(true);
        $selfVetOption = SelfVetOption::getDefault();
        $numberOfTokensPerIdentityOption = new NumberOfTokensPerIdentityOption(2);

        $firstRaLocation                 = RaLocation::create(
            (string) Uuid::uuid4(),
            $institution,
            new RaLocationName('Some Location'),
            new Location('Somewhere here or there'),
            new ContactInformation('Per phone.')
        );
        $secondRaLocation                = RaLocation::create(
            (string) Uuid::uuid4(),
            $institution,
            new RaLocationName('Somewhere else'),
            new Location('Utrecht, The Netherlands'),
            new ContactInformation('Shout really hard')
        );
        $raLocations                     = [$firstRaLocation, $secondRaLocation];

        $mapped = new MappedInstitutionConfiguration(
            $institution,
            $useRaLocationsOption,
            $showRaaContactInformationOption,
            $verifyEmailOption,
            $selfVetOption,
            $numberOfTokensPerIdentityOption,
            $raLocations
        );

        $commands = $mapped->inferAddRaLocationCommands();

        $this->assertEquals(2, count($commands));
        $this->assertCommandMatches($commands[0], $institution, $firstRaLocation);
        $this->assertCommandMatches($commands[1], $institution, $secondRaLocation);
    }

    public function assertCommandMatches(
        AddRaLocationCommand $command,
        Institution $institution,
        RaLocation $raLocation
    ) {
        $this->assertEquals($institution->getInstitution(), $command->institution);
        $this->assertEquals($raLocation->id, $command->raLocationId);
        $this->assertEquals($raLocation->name->getRaLocationName(), $command->raLocationName);
        $this->assertEquals($raLocation->location->getLocation(), $command->location);
        $this->assertEquals(
            $raLocation->contactInformation->getContactInformation(),
            $command->contactInformation
        );
    }
}

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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\EventListener;

use Mockery;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener\WhitelistEventListener;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class WhitelistEventListenerTest extends TestCase
{
    /**
     * @test
     * @group event-listener
     */
    public function create_institution_configuration_commands_are_processed_when_a_whitelist_was_created_containing_non_configured_institutions()
    {
        $firstInstitution = 'First institution';
        $secondInstitution = 'Second institution';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(new InstitutionCollection([
            new Institution($firstInstitution),
            new Institution($secondInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $firstExpectedCommand->institution = new ConfigurationInstitution($firstInstitution);

        $secondExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $secondExpectedCommand->institution = new ConfigurationInstitution($secondInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $firstActualCommand) use ($firstExpectedCommand) {
                $this->assertEquals($firstExpectedCommand->institution, $firstActualCommand->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $secondActualCommand) use ($secondExpectedCommand) {
                $this->assertEquals($secondExpectedCommand->institution, $secondActualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function no_create_institution_configuration_commands_are_processed_for_an_already_configured_institution_when_a_whitelist_was_created()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution = 'New';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedCommand              = new CreateInstitutionConfigurationCommand;
        $expectedCommand->institution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $actualCommand) use ($expectedCommand) {
                $this->assertEquals($expectedCommand->institution, $actualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function create_institution_configuration_commands_are_processed_when_a_whitelist_was_replaced_containing_non_configured_institutions()
    {
        $firstInstitution = 'First institution';
        $secondInstitution = 'Second institution';

        $whitelistReplacedEvent = new WhitelistReplacedEvent(new InstitutionCollection([
            new Institution($firstInstitution),
            new Institution($secondInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $firstExpectedCommand->institution = new ConfigurationInstitution($firstInstitution);

        $secondExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $secondExpectedCommand->institution = new ConfigurationInstitution($secondInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $firstActualCommand) use ($firstExpectedCommand) {
                $this->assertEquals($firstExpectedCommand->institution, $firstActualCommand->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $secondActualCommand) use ($secondExpectedCommand) {
                $this->assertEquals($secondExpectedCommand->institution, $secondActualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyWhitelistReplacedEvent($whitelistReplacedEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function no_create_institution_configuration_commands_are_processed_for_an_already_configured_institution_when_a_whitelist_was_replaced()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution = 'New';

        $whitelistCreatedEvent = new WhitelistReplacedEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedCommand              = new CreateInstitutionConfigurationCommand;
        $expectedCommand->institution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $actualCommand) use ($expectedCommand) {
                $this->assertEquals($expectedCommand->institution, $actualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyWhitelistReplacedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function create_institution_configuration_commands_are_processed_when_non_configured_institutions_are_added_to_the_whitelist()
    {
        $firstInstitution = 'First institution';
        $secondInstitution = 'Second institution';

        $institutionsAddedToWhitelistEvent = new InstitutionsAddedToWhitelistEvent(new InstitutionCollection([
            new Institution($firstInstitution),
            new Institution($secondInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $firstExpectedCommand->institution = new ConfigurationInstitution($firstInstitution);

        $secondExpectedCommand              = new CreateInstitutionConfigurationCommand;
        $secondExpectedCommand->institution = new ConfigurationInstitution($secondInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $firstActualCommand) use ($firstExpectedCommand) {
                $this->assertEquals($firstExpectedCommand->institution, $firstActualCommand->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $secondActualCommand) use ($secondExpectedCommand) {
                $this->assertEquals($secondExpectedCommand->institution, $secondActualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyInstitutionsAddedToWhitelistEvent($institutionsAddedToWhitelistEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function no_create_institution_configuration_commands_are_processed_for_an_already_configured_institution_when_institutions_are_added_to_a_whitelist()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution = 'New';

        $whitelistCreatedEvent = new InstitutionsAddedToWhitelistEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedCommand              = new CreateInstitutionConfigurationCommand;
        $expectedCommand->institution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $actualCommand) use ($expectedCommand) {
                $this->assertEquals($expectedCommand->institution, $actualCommand->institution);
            });


        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $pipelineMock);
        $whitelistEventListener->applyInstitutionsAddedToWhitelistEvent($whitelistCreatedEvent);
    }
}

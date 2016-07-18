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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Configuration\Processor;

use Mockery;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Processor\InstitutionConfigurationProcessor;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class InstitutionConfigurationProcessorTest extends TestCase
{
    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function a_create_institution_configuration_command_is_processed_when_an_identity_was_created_with_a_non_configured_institution()
    {
        $identityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId('Id'),
            new Institution('Institution'),
            new NameId('Name Id'),
            new CommonName('Common name'),
            new Email('test@email.test'),
            new Locale('Locale')
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution(
            $identityCreatedEvent->identityInstitution->getInstitution()
        );

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing((function (CreateInstitutionConfigurationCommand $command) use ($expectedInstitution) {
                $this->assertEquals($expectedInstitution, $command->institution);
            }));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyIdentityCreatedEvent($identityCreatedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_when_an_identity_was_created_with_an_already_configured_institution()
    {
        $identityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId('Id'),
            new Institution('Institution'),
            new NameId('Name Id'),
            new CommonName('Common name'),
            new Email('test@email.test'),
            new Locale('Locale')
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(true);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldNotReceive('process');

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyIdentityCreatedEvent($identityCreatedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_processed_when_a_whitelist_was_created_containing_non_configured_institutions()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitutionName),
                    new Institution($secondInstitutionName),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution  = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($firstExpectedInstitution) {
                $this->assertEquals($firstExpectedInstitution, $command->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($secondExpectedInstitution) {
                $this->assertEquals($secondExpectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_for_an_already_configured_institution_when_a_whitelist_was_created()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($expectedInstitution) {
                $this->assertEquals($expectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_created_when_a_whitelist_was_replaced_containing_non_configured_institutions()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $whitelistReplacedEvent = new WhitelistReplacedEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitutionName),
                    new Institution($secondInstitutionName),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution  = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($firstExpectedInstitution) {
                $this->assertEquals($firstExpectedInstitution, $command->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($secondExpectedInstitution) {
                $this->assertEquals($secondExpectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyWhitelistReplacedEvent($whitelistReplacedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_for_an_already_configured_institution_when_a_whitelist_was_replaced()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new WhitelistReplacedEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($expectedInstitution) {
                $this->assertEquals($expectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyWhitelistReplacedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_created_when_non_configured_institutions_are_added_to_the_whitelist()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $institutionsAddedToWhitelistEvent = new InstitutionsAddedToWhitelistEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitutionName),
                    new Institution($secondInstitutionName),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution  = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($firstExpectedInstitution) {
                $this->assertEquals($firstExpectedInstitution, $command->institution);
            });
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($secondExpectedInstitution) {
                $this->assertEquals($secondExpectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyInstitutionsAddedToWhitelistEvent($institutionsAddedToWhitelistEvent);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_created_for_an_already_configured_institution_when_institutions_are_added_to_a_whitelist()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new InstitutionsAddedToWhitelistEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (CreateInstitutionConfigurationCommand $command) use ($expectedInstitution) {
                $this->assertEquals($expectedInstitution, $command->institution);
            });

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor($repositoryMock, $pipelineMock);
        $institutionConfigurationProcessor->applyInstitutionsAddedToWhitelistEvent($whitelistCreatedEvent);
    }
}

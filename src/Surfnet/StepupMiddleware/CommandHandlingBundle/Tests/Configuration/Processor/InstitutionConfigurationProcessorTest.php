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
use PHPUnit\Framework\TestCase as TestCase;
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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Processor\InstitutionConfigurationProcessor;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery\HasInstitutionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstitutionConfigurationProcessorTest extends TestCase
{
    private $pipelineMock;

    /**
     * @return Mockery\MockInterface
     */
    public function setUp(): void
    {
        $this->pipelineMock = Mockery::mock(Pipeline::class);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function a_create_institution_configuration_command_is_processed_when_an_identity_was_created_with_a_non_configured_institution(): void
    {
        $expectedInstitution  = 'institution';
        $identityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId('Id'),
            new Institution($expectedInstitution),
            new NameId('Name Id'),
            new CommonName('Common name'),
            new Email('test@email.test'),
            new Locale('Locale')
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->with(new HasInstitutionMatcher($expectedInstitution))
            ->once();

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleIdentityCreatedEvent($identityCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_when_an_identity_was_created_with_an_already_configured_institution(): void
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
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->andReturn(true);

        $this->pipelineMock
            ->shouldReceive('process')
            ->never();

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleIdentityCreatedEvent($identityCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_processed_when_a_whitelist_was_created_containing_non_configured_institutions(): void
    {
        $firstInstitution  = 'first institution';
        $secondInstitution = 'second institution';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitution),
                    new Institution($secondInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($firstInstitution));
        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($secondInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleWhitelistCreatedEvent($whitelistCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_for_an_already_configured_institution_when_a_whitelist_was_created(): void
    {
        $alreadyPresentInstitution = 'already present';
        $newInstitution            = 'new';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->with(new HasInstitutionMatcher($alreadyPresentInstitution))
            ->once()
            ->andReturn(true);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->with(new HasInstitutionMatcher($newInstitution))
            ->once()
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($newInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleWhitelistCreatedEvent($whitelistCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_created_when_a_whitelist_was_replaced_containing_non_configured_institutions(): void
    {
        $firstInstitution  = 'first institution';
        $secondInstitution = 'second institution';

        $whitelistReplacedEvent = new WhitelistReplacedEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitution),
                    new Institution($secondInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->twice()
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($firstInstitution));
        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($secondInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleWhitelistReplacedEvent($whitelistReplacedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_processed_for_an_already_configured_institution_when_a_whitelist_was_replaced(): void
    {
        $alreadyPresentInstitution = 'already present';
        $newInstitution            = 'new';

        $whitelistCreatedEvent = new WhitelistReplacedEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->once()
            ->with(new HasInstitutionMatcher($alreadyPresentInstitution))
            ->andReturn(true);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->once()
            ->with(new HasInstitutionMatcher($newInstitution))
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($newInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleWhitelistReplacedEvent($whitelistCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function create_institution_configuration_commands_are_created_when_non_configured_institutions_are_added_to_the_whitelist(): void
    {
        $firstInstitution  = 'first institution';
        $secondInstitution = 'second institution';

        $institutionsAddedToWhitelistEvent = new InstitutionsAddedToWhitelistEvent(
            new InstitutionCollection(
                [
                    new Institution($firstInstitution),
                    new Institution($secondInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->twice()
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($firstInstitution));
        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($secondInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleInstitutionsAddedToWhitelistEvent($institutionsAddedToWhitelistEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @test
     * @group processor
     * @group institution-configuration
     */
    public function no_create_institution_configuration_command_is_created_for_an_already_configured_institution_when_institutions_are_added_to_a_whitelist(): void
    {
        $alreadyPresentInstitution = 'already present';
        $newInstitution            = 'new';

        $whitelistCreatedEvent = new InstitutionsAddedToWhitelistEvent(
            new InstitutionCollection(
                [
                    new Institution($alreadyPresentInstitution),
                    new Institution($newInstitution),
                ]
            )
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->once()
            ->with(new HasInstitutionMatcher($alreadyPresentInstitution))
            ->andReturn(true);
        $repositoryMock
            ->shouldReceive('hasConfigurationFor')
            ->once()
            ->with(new HasInstitutionMatcher($newInstitution))
            ->andReturn(false);

        $this->pipelineMock
            ->shouldReceive('process')
            ->once()
            ->with(new HasInstitutionMatcher($newInstitution));

        $institutionConfigurationProcessor = new InstitutionConfigurationProcessor(
            $repositoryMock,
            $this->getContainerMock()
        );
        $institutionConfigurationProcessor->handleInstitutionsAddedToWhitelistEvent($whitelistCreatedEvent);

        $this->assertInstanceOf(InstitutionConfigurationProcessor::class, $institutionConfigurationProcessor);
    }

    /**
     * @return ContainerInterface
     */
    private function getContainerMock()
    {
        $containerMock = Mockery::mock(ContainerInterface::class);
        $containerMock
            ->shouldReceive('get')
            ->with('pipeline')
            ->andReturn($this->pipelineMock);

        return $containerMock;
    }
}

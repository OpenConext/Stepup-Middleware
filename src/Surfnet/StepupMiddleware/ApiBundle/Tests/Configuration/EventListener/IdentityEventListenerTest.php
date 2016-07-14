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
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener\IdentityEventListener;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class IdentityEventListenerTest extends TestCase
{
    /**
     * @test
     * @group event-listener
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

        $expectedCommand              = new CreateInstitutionConfigurationCommand;
        $expectedCommand->institution = new ConfigurationInstitution(
            $identityCreatedEvent->identityInstitution->getInstitution()
        );

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturnUsing(function(CreateInstitutionConfigurationCommand $actualCommand) use ($expectedCommand) {
                $this->assertEquals($expectedCommand->institution, $actualCommand->institution);
            });

        $identityEventListener = new IdentityEventListener($repositoryMock, $pipelineMock);
        $identityEventListener->applyIdentityCreatedEvent($identityCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
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

        $expectedCommand              = new CreateInstitutionConfigurationCommand;
        $expectedCommand->institution = new ConfigurationInstitution(
            $identityCreatedEvent->identityInstitution->getInstitution()
        );

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldNotReceive('process');

        $identityEventListener = new IdentityEventListener($repositoryMock, $pipelineMock);
        $identityEventListener->applyIdentityCreatedEvent($identityCreatedEvent);
    }
}

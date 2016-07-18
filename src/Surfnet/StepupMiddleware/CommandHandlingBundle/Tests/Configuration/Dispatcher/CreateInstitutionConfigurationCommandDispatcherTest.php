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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Configuration\Dispatcher;

use Mockery;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Dispatcher\CreateInstitutionConfigurationCommandDispatcher;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class CreateInstitutionConfigurationCommandDispatcherTest extends TestCase
{
    /**
     * @test
     * @group institution-configuration
     */
    public function a_create_institution_configuration_command_is_fired_when_creating_a_institution_configuration()
    {
        $expectedInstitution = new Institution('The institution');

        $pipelineMock = Mockery::mock(Pipeline::class);
        $pipelineMock->shouldReceive('process')
            ->once()
            ->andReturn(Mockery::on(function($command) use ($expectedInstitution) {
                $this->assertInstanceOf(CreateInstitutionConfigurationCommand::class, $command);
                $this->assertEquals($expectedInstitution, $command->institution);
            }));

        $dispatcher = new CreateInstitutionConfigurationCommandDispatcher($pipelineMock);
        $dispatcher->createConfigurationFor($expectedInstitution);
    }
}

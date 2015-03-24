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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Pipeline;

use Mockery as m;
use PHPUnit_Framework_TestCase as UnitTest;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\EventDispatchingStage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Command\FixedUuidStubCommand;

class EventDispatchingStageTest extends UnitTest
{
    /**
     * @test
     * @group pipeline
     */
    public function buffered_event_bus_flush_is_called_during_process()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $eventBus = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus')
            ->shouldReceive('flush')->once()
            ->getMock();

        $stage = new EventDispatchingStage(new NullLogger(), $eventBus);
        $stage->process($command);
    }

    /**
     * @test
     * @group pipeline
     */
    public function it_returns_the_same_command_as_it_processes_unmodified()
    {
        $command = new FixedUuidStubCommand();
        $uuid = $command->UUID;

        $eventBus = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus')
            ->shouldReceive('flush')->once()
            ->getMock();

        $stage = new EventDispatchingStage(new NullLogger(), $eventBus);
        $returnedCommand = $stage->process($command);

        $this->assertSame($command, $returnedCommand);
        $this->assertEquals($uuid, $returnedCommand->UUID);
    }
}

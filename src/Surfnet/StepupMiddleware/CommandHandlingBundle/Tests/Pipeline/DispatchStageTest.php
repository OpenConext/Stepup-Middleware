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

use Broadway\CommandHandling\CommandBus;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\DispatchStage;

class DispatchStageTest extends TestCase
{
    /**
     * @test
     * @group pipeline
     */
    public function it_dispatches_commands()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $commandBus = m::mock(CommandBus::class)->makePartial()
            ->shouldReceive('dispatch')->once()->with($command)->andReturnNull()
            ->getMock();

        $stage = new DispatchStage(new NullLogger(), $commandBus);

        $this->assertSame($command, $stage->process($command));
    }
}

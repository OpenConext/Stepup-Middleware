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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\DispatchStage;

class DispatchStageTest extends \PHPUnit_Framework_TestCase
{
    public function testItDispatchesCommands()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $commandBus = m::mock('Broadway\CommandHandling\CommandBusInterface')
            ->shouldReceive('dispatch')->once()->with($command)->andReturnNull()
            ->getMock();

        $stage = new DispatchStage($commandBus);

        $this->assertSame($command, $stage->process($command));
    }
}

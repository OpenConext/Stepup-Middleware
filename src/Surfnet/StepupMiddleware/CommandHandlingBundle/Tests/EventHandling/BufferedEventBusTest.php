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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\EventHandling;

use Broadway\Domain\DomainEventStream;
use Mockery as m;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;

class BufferedEventBusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group event-handling
     */
    public function it_buffers_events()
    {
        $event = m::mock('Broadway\Domain\DomainMessageInterface');
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->never()
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        // Currently buses typehint against the concrete DomainEventStream.
        $bus->publish(new DomainEventStream([$event]));
    }

    /**
     * @test
     * @group event-handling
     */
    public function it_flushes_events()
    {
        $event = m::mock('Broadway\Domain\DomainMessageInterface');
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->once()->with($event)
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        // Currently buses typehint against the concrete DomainEventStream.
        $bus->publish(new DomainEventStream([$event]));
        $bus->flush();
    }
}

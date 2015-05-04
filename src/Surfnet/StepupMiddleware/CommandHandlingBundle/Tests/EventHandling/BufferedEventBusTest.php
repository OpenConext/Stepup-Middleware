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

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
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
        $event = $this->createDummyDomainMessage();
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
        $event = $this->createDummyDomainMessage();
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->once()->with($event)
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        // Currently buses typehint against the concrete DomainEventStream.
        $bus->publish(new DomainEventStream([$event]));
        $bus->flush();
    }

    /**
     * This is tested by checking that the mock event listener is invoked once, while flushing twice.
     * @test
     * @group event-handling
     */
    public function flushing_succesfully_empties_the_buffer_to_prevent_flushing_the_same_event_twice()
    {
        $event    = $this->createDummyDomainMessage();
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->once()->with($event)
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        $bus->publish(new DomainEventStream([$event]));
        $bus->flush();
        $bus->flush();
    }

    /**
     * This is tested by publishing an event when flushing and flushing again afterwards
     *
     * @test
     * @group event-handling
     */
    public function new_event_can_be_buffered_while_flushing()
    {
        $event = $this->createDummyDomainMessage();
        $bus = new BufferedEventBus();

        // in php7 replace this with anonymous class
        $listener = new OnFirstCallPublishesToBusAndCountingCallsEventListener(
            $bus,
            new DomainEventStream([$event])
        );

        $bus->subscribe($listener);

        $bus->publish(new DomainEventStream([$event]));

        $this->assertEquals(0, $listener->callCount, 'Prior to the first flush, the callcount should be 0');
        $bus->flush();
        $bus->flush();

        $this->assertEquals(2, $listener->callCount, 'After flushing twice, the callcount should be 2');
    }

    /**
     * @return DomainMessage
     */
    private function createDummyDomainMessage()
    {
        return new DomainMessage('1', 0, new Metadata(), null, DateTime::fromString('1970-01-01H00:00:00.000'));
    }
}

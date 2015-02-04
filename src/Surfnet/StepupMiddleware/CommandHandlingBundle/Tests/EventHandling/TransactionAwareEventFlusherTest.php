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

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\TransactionAwareEventFlusher;

class TransactionAwareEventFlusherTest extends TestCase
{
    /** @test */
    public function transaction_is_committed()
    {
        $eventBus = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus');
        $eventBus->shouldReceive('flush')->once();

        $middlewareConnection = m::mock('Doctrine\DBAL\Driver\Connection');
        $middlewareConnection->shouldReceive('beginTransaction')->once();
        $middlewareConnection->shouldReceive('commit')->once();

        $gatewayConnection = m::mock('Doctrine\DBAL\Driver\Connection');
        $gatewayConnection->shouldReceive('beginTransaction')->once();
        $gatewayConnection->shouldReceive('commit')->once();

        $flusher = new TransactionAwareEventFlusher($eventBus, $middlewareConnection, $gatewayConnection);
        $flusher->flush();
    }

    /** @test */
    public function transaction_is_rolled_back_on_exceptions_and_exceptions_are_rethrown()
    {
        $eventBus = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus');
        $eventBus->shouldReceive('flush')->once()->andThrow(new \RuntimeException('Deeeerp'));

        $middlewareConnection = m::mock('Doctrine\DBAL\Driver\Connection');
        $middlewareConnection->shouldReceive('beginTransaction')->once();
        $middlewareConnection->shouldReceive('rollBack')->once();

        $gatewayConnection = m::mock('Doctrine\DBAL\Driver\Connection');
        $gatewayConnection->shouldReceive('beginTransaction')->once();
        $gatewayConnection->shouldReceive('rollBack')->once();

        $this->setExpectedException('RuntimeException');

        $flusher = new TransactionAwareEventFlusher($eventBus, $middlewareConnection, $gatewayConnection);
        $flusher->flush();
    }
}

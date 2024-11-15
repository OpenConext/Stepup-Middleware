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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling;

use Broadway\Domain\DomainEventStream as DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventHandling\EventListener as EventListenerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\Exception;
use Throwable;

class BufferedEventBus implements EventBusInterface
{
    /**
     * @var EventListenerInterface[]
     */
    private array $eventListeners = [];

    /**
     * @var DomainMessage[]
     */
    private array $buffer = [];

    /**
     * Flag to ensure only one loop is publishing domain messages from the buffer.
     */
    private bool $isFlushing = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function subscribe(EventListenerInterface $eventListener): void
    {
        $this->eventListeners[] = $eventListener;
    }

    public function publish(DomainEventStreamInterface $domainMessages): void
    {
        foreach ($domainMessages as $domainMessage) {
            $this->buffer[] = $domainMessage;
        }
    }

    /**
     * Flushes the buffered domain messages to all event listeners.
     * @throws Exception
     */
    public function flush(): void
    {
        if ($this->isFlushing) {
            // If already flushing, we're in a nested pipeline. This means that an event that is currently being
            // handled, triggered a command. This command caused events, which are collected in the buffer.
            // These events may only be flushed when all current events have been handled.
            // Therefore, we return here and check if there are events in the buffer after handling all current events.
            return;
        }

        $this->isFlushing = true;

        // swap the buffer so we can still publish new events, during or after flush
        $buffer = $this->buffer;
        $this->buffer = [];

        try {
            while ($domainMessage = array_shift($buffer)) {
                foreach ($this->eventListeners as $eventListener) {
                    $eventListener->handle($domainMessage);
                }

                // After handling an event, clear the entity manager to prevent collisions in Doctrine's object tracking
                // This comes with a caveat: event listeners cannot hold references to certain entities between events
                $this->entityManager->clear();
            }
        } catch (Throwable $e) {
            $this->isFlushing = false;

            array_splice($this->buffer, 0, 0, $buffer);

            throw $e;
        }

        $this->isFlushing = false;
        unset($buffer);

        // if during the handling of events new events have been queued, we need to flush them
        if (count($this->buffer) > 0) {
            $this->flush();
        }
    }
}

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

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessageInterface;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;

class BufferedEventBus implements EventBusInterface
{
    /**
     * @var EventListenerInterface[]
     */
    private $eventListeners = [];

    /**
     * @var DomainMessageInterface[]
     */
    private $buffer = [];

    /**
     * Flag to ensure only one loop is publishing domain messages from the buffer.
     *
     * @var bool
     */
    private $isPublishing = false;

    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->eventListeners[] = $eventListener;
    }

    public function publish(DomainEventStreamInterface $domainMessages)
    {
        foreach ($domainMessages as $domainMessage) {
            $this->buffer[] = $domainMessage;
        }
    }

    /**
     * Flushes the buffered domain messages to all event listeners.
     */
    public function flush()
    {
        if (!$this->isPublishing) {
            $this->isPublishing = true;

            while ($domainMessage = array_shift($this->buffer)) {
                foreach ($this->eventListeners as $eventListener) {
                    $eventListener->handle($domainMessage);
                }
            }

            $this->isPublishing = false;
        }
    }
}

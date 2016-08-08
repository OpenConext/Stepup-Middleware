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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;

class RecordEventsAndPublishToBusOnFirstCallEventListener implements EventListenerInterface
{
    /**
     * @var bool
     */
    private $firstEventHandled = false;

    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @var DomainEventStream
     */
    private $toPublish;

    /**
     * @var DomainMessage[]
     */
    private $recordedEvents = [];

    /**
     * @param BufferedEventBus $eventBus
     * @param DomainEventStream $toPublish
     */
    public function __construct(BufferedEventBus $eventBus, DomainEventStream $toPublish)
    {
        $this->eventBus  = $eventBus;
        $this->toPublish = $toPublish;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $this->recordedEvents[] = $domainMessage;

        if (!$this->firstEventHandled) {
            $this->eventBus->publish($this->toPublish);
            $this->firstEventHandled = true;
        }
    }

    /**
     * @return DomainMessage[]
     */
    public function getRecordedEvents()
    {
        return $this->recordedEvents;
    }
}

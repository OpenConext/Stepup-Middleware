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
use Broadway\Domain\DomainMessageInterface;
use Broadway\EventHandling\EventListenerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;

class OnFirstCallPublishesToBusAndCountingCallsEventListener implements EventListenerInterface
{
    /**
     * @var int
     */
    public $callCount = 0;

    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @var DomainEventStream
     */
    private $toPublish;

    /**
     * @param BufferedEventBus  $eventBus
     * @param DomainEventStream $toPublish
     */
    public function __construct(BufferedEventBus $eventBus, DomainEventStream $toPublish)
    {
        $this->eventBus = $eventBus;
        $this->toPublish = $toPublish;
    }

    public function handle(DomainMessageInterface $domainMessage)
    {
        if ($this->callCount === 0) {
            $this->eventBus->publish($this->toPublish);
        }

        $this->callCount++;
    }
}

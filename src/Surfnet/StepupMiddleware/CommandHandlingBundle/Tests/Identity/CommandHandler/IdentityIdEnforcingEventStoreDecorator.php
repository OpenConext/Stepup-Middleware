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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\Domain\DomainEventStream as DomainEventStreamInterface;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Surfnet\Stepup\Identity\Event\IdentityEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;

final class IdentityIdEnforcingEventStoreDecorator implements EventStoreInterface
{
    /**
     * @var EventStoreInterface
     */
    private $decoratedEventStore;

    public function __construct(EventStoreInterface $decoratedEventStore)
    {
        $this->decoratedEventStore = $decoratedEventStore;
    }

    public function load($id): DomainEventStreamInterface
    {
        $eventStream = $this->decoratedEventStore->load($id);

        $this->assertIdentityAggregate($eventStream);

        return $eventStream;
    }

    public function append($id, DomainEventStreamInterface $eventStream): void
    {
        $this->assertIdentityAggregate($eventStream);

        $this->decoratedEventStore->append($id, $eventStream);
    }

    /**
     * @param $id
     * @param int $playhead
     * @return DomainEventStreamInterface
     */
    public function loadFromPlayhead($id, int $playhead): DomainEventStreamInterface
    {
        $eventStream = $this->decoratedEventStore->loadFromPlayhead($id, $playhead);

        $this->assertIdentityAggregate($eventStream);

        return $eventStream;
    }

    /**
     * @param DomainEventStreamInterface $stream
     */
    public function assertIdentityAggregate(DomainEventStreamInterface $stream)
    {
        foreach ($stream as $message) {
            if (!$message->getPayload() instanceof IdentityEvent) {
                throw new InvalidArgumentException(
                    'The SensitiveDataEventStoreDecorator only works with Identities, please pass in an IdentityId $id'
                );
            }
        }
    }
}

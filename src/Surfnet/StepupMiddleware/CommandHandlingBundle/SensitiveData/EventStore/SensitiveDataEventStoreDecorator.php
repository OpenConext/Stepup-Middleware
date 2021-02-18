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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventStore;

use Broadway\Domain\DomainEventStream as DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Surfnet\Stepup\Identity\Event\IdentityEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessageStream;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository;

/**
 * Event store decorator that loads and appends the sensitive data of events into a separate data store.
 */
final class SensitiveDataEventStoreDecorator implements EventStoreInterface
{
    /**
     * @var EventStoreInterface
     */
    private $decoratedEventStore;

    /**
     * @var SensitiveDataMessageRepository
     */
    private $sensitiveDataMessageRepository;

    /**
     * @param EventStoreInterface $decoratedEventStore
     * @param SensitiveDataMessageRepository $sensitiveDataMessageRepository
     */
    public function __construct(
        EventStoreInterface $decoratedEventStore,
        SensitiveDataMessageRepository $sensitiveDataMessageRepository
    ) {
        $this->decoratedEventStore = $decoratedEventStore;
        $this->sensitiveDataMessageRepository = $sensitiveDataMessageRepository;
    }

    public function load($id): DomainEventStreamInterface
    {
        $domainEventStream = $this->decoratedEventStore->load($id);

        $this->assertIdentityAggregate($domainEventStream);

        $sensitiveDataStream = $this->sensitiveDataMessageRepository->findByIdentityId($id);
        $sensitiveDataStream->applyToDomainEventStream($domainEventStream);

        return $domainEventStream;
    }

    public function append($id, DomainEventStreamInterface $eventStream): void
    {
        $this->assertIdentityAggregate($eventStream);

        $this->decoratedEventStore->append($id, $eventStream);

        $sensitiveDataMessages = [];
        foreach ($eventStream as $message) {
            /** @var DomainMessage $message */
            $event = $message->getPayload();

            if (!$event instanceof Forgettable) {
                continue;
            }

            $sensitiveDataMessages[] = new SensitiveDataMessage(
                $id,
                $message->getPlayhead(),
                $event->getSensitiveData()
            );
        }

        $this->sensitiveDataMessageRepository->append(new SensitiveDataMessageStream($sensitiveDataMessages));
    }

    /**
     * @param $id
     * @param int $playhead
     * @return DomainEventStreamInterface
     */
    public function loadFromPlayhead($id, int $playhead): DomainEventStreamInterface
    {
        $domainEventStream = $this->decoratedEventStore->loadFromPlayhead($id, $playhead);

        $this->assertIdentityAggregate($domainEventStream);

        $sensitiveDataStream = $this->sensitiveDataMessageRepository->findByIdentityId($id);
        $sensitiveDataStream->applyToDomainEventStream($domainEventStream);

        return $domainEventStream;
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

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

namespace Surfnet\Stepup\Identity\EventSourcing;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\AggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventSourcing\EventStreamDecorator;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Serializer\Serializable;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;

class IdentityRepository extends EventSourcingRepository
{
    protected $events;

    protected $logger;

    /**
     * @param EventStreamDecorator[] $eventStreamDecorators
     */
    public function __construct(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
        AggregateFactory $aggregateFactory,
        LoggerInterface $logger,
        array $eventStreamDecorators = []
    ) {
        $this->events = $eventStore;
        $this->logger = $logger;
        parent::__construct(
            $eventStore,
            $eventBus,
            Identity::class,
            $aggregateFactory,
            $eventStreamDecorators
        );
    }

    public function obtainInformation(IdentityId $id): array
    {
        try {
            $domainEventStream = $this->events->load($id);
            $data = [];
            /** @var DomainMessage $domainMessage */
            foreach ($domainEventStream as $domainMessage) {
                $event = $domainMessage->getPayload();
                if (!$event instanceof Serializable) {
                    $this->logger->warning(
                        sprintf(
                            'Unable to serialize event type %s, unable to return user information for that event',
                            $domainMessage->getType()
                        )
                    );
                }
                // A combination of the playhead and event name are used to index the data array
                $index = $domainMessage->getPlayhead() . '-' . $domainMessage->getType();
                // The data is retrieved from the payload (the event object), sensitive data is merged into it
                $eventData = $event->serialize();
                if ($event instanceof Forgettable) {
                    $eventData += $event->getSensitiveData()->serialize();
                }

                $data[$index] = $eventData;
            }
            return $data;
        } catch (EventStreamNotFoundException $e) {
            throw AggregateNotFoundException::create($id, $e);
        }
    }
}

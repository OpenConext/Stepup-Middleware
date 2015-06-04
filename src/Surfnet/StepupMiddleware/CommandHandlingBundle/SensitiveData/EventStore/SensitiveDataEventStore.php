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

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Entity\SensitiveDataMessage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataEventStoreException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;

/**
 * Event store decorator that loads and appends the sensitive data of events into a separate data store.
 */
final class SensitiveDataEventStore implements EventStoreInterface
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

    public function load($id)
    {
        $messageStream = $this->decoratedEventStore->load($id);
        $sensitiveDataStream = $this->sensitiveDataMessageRepository->findByIdentityId(new IdentityId($id));
        reset($sensitiveDataStream);

        /** @var DomainMessage $message */
        foreach ($messageStream as $message) {
            /** @var SensitiveDataMessage|false $sensitiveDataMessage */
            $sensitiveDataMessage = current($sensitiveDataStream);

            if (!$sensitiveDataMessage || $sensitiveDataMessage->getPlayhead() != $message->getPlayhead()) {
                continue;
            }

            $event = $message->getPayload();

            if (!$event instanceof Forgettable) {
                throw new SensitiveDataEventStoreException(
                    'Encountered sensitive data for event which does not support it'
                );
            }

            $event->setSensitiveData($sensitiveDataMessage->getSensitiveData());

            next($sensitiveDataStream);
        }

        return $messageStream;
    }

    public function append($id, DomainEventStreamInterface $eventStream)
    {
        $this->decoratedEventStore->append($id, $eventStream);

        $sensitiveDataMessages = [];
        $identityId = new IdentityId($id);

        /** @var DomainMessage $message */
        foreach ($eventStream as $message) {
            $event = $message->getPayload();

            if (!$event instanceof Forgettable) {
                continue;
            }

            $sensitiveDataMessages[] = new SensitiveDataMessage(
                $identityId,
                $message->getPlayhead(),
                $event->getSensitiveData()
            );
        }

        $this->sensitiveDataMessageRepository->append($sensitiveDataMessages);
    }
}

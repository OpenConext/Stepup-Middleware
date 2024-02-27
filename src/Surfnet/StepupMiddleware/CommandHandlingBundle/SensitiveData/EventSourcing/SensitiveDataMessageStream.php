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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing;

use ArrayIterator;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use IteratorAggregate;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;

class SensitiveDataMessageStream implements IteratorAggregate
{
    private array $messages;

    /**
     * @param SensitiveDataMessage[] $messages
     */
    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    public function applyToDomainEventStream(DomainEventStream $domainEventStream): void
    {
        $sensitiveDataMap = $this->createSensitiveDataMap($this->messages);

        /** @var DomainMessage $domainMessage */
        foreach ($domainEventStream as $domainMessage) {
            $sensitiveDataMessage = isset($sensitiveDataMap[$domainMessage->getPlayhead()])
                ? $sensitiveDataMap[$domainMessage->getPlayhead()]
                : null;
            unset($sensitiveDataMap[$domainMessage->getPlayhead()]);

            $this->setSensitiveData($domainMessage, $sensitiveDataMessage);
        }

        if ($sensitiveDataMap !== []) {
            throw new SensitiveDataApplicationException(sprintf(
                '%d sensitive data messages are still to be matched to events',
                count($sensitiveDataMap)
            ));
        }
    }

    public function forget(): void
    {
        foreach ($this->messages as $message) {
            $message->forget();
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->messages);
    }

    /**
     * @param DomainMessage $domainMessage
     * @param SensitiveDataMessage|null $sensitiveDataMessage
     */
    private function setSensitiveData(DomainMessage $domainMessage, SensitiveDataMessage $sensitiveDataMessage = null): void
    {
        $event = $domainMessage->getPayload();
        $eventIsForgettable = $event instanceof Forgettable;

        if (!$eventIsForgettable && !$sensitiveDataMessage) {
            return;
        }

        if ($eventIsForgettable && !$sensitiveDataMessage) {
            throw new SensitiveDataApplicationException(sprintf(
                'Sensitive data is missing for event with UUID %s, playhead %d',
                $domainMessage->getId(),
                $domainMessage->getPlayhead()
            ));
        }

        if (!$eventIsForgettable && $sensitiveDataMessage) {
            throw new SensitiveDataApplicationException(sprintf(
                'Encountered sensitive data for event which does not support sensitive data, UUID %s, playhead %d',
                $domainMessage->getId(),
                $domainMessage->getPlayhead()
            ));
        }

        if ($domainMessage->getId() != $sensitiveDataMessage->getIdentityId()) {
            throw new SensitiveDataApplicationException(sprintf(
                'Encountered sensitive data from stream %s for event from stream %s',
                $sensitiveDataMessage->getIdentityId(),
                $domainMessage->getId()
            ));
        }

        $event->setSensitiveData($sensitiveDataMessage->getSensitiveData());
    }

    /**
     * @param SensitiveDataMessage[] $messages
     * @return SensitiveDataMessage[] The same messages, but indexed by their playheads.
     */
    private function createSensitiveDataMap(array $messages): array
    {
        $map = [];
        foreach ($messages as $message) {
            $map[$message->getPlayhead()] = $message;
        }

        return $map;
    }
}

<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing;

use ArrayIterator;
use IteratorAggregate;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

final class EventCollection implements IteratorAggregate
{
    /**
     * @var string[]
     */
    private array $eventNames = [];

    public function __construct(array $eventNames)
    {
        foreach ($eventNames as $eventName) {
            if (!is_string($eventName) || empty($eventName)) {
                throw InvalidArgumentException::invalidType('non-empty string', 'eventName', $eventName);
            }

            if (!class_exists($eventName)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot create EventCollection: class "%s" does not exist',
                    $eventName
                ));
            }

            $this->eventNames[] = $eventName;
        }
    }

    /**
     * @return string[]
     */
    public function getEventNames()
    {
        return $this->eventNames;
    }

    public function formatAsEventStreamTypes(): array
    {
        return array_map(
            function ($eventName): string {
                return strtr($eventName, '\\', '.');
            },
            $this->eventNames
        );
    }

    /**
     * @param array $subset
     * @return EventCollection
     */
    public function select(array $subset): self
    {
        $nonAvailableEventNames = array_diff($subset, $this->eventNames);

        if ($nonAvailableEventNames !== []) {
            throw new InvalidArgumentException(
                sprintf(
                    'Subset of event names contains event names not present in collection: %s',
                    implode(', ', $nonAvailableEventNames)
                )
            );
        }

        return new self($subset);
    }

    /**
     * @param $eventName
     * @return bool
     */
    public function contains($eventName): bool
    {
        return in_array($eventName, $this->eventNames);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->eventNames);
    }
}

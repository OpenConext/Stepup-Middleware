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

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\Identity\Value\IdentityId;
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

    public function load($id)
    {
        if (!$id instanceof IdentityId) {
            throw new InvalidArgumentException(
                'The SensitiveDataEventStoreDecorator only works with Identities, please pass in an IdentityId $id'
            );
        }

        return $this->decoratedEventStore->load($id);
    }

    public function append($id, DomainEventStreamInterface $eventStream)
    {
        if (!$id instanceof IdentityId) {
            throw new InvalidArgumentException(
                'The SensitiveDataEventStoreDecorator only works with Identities, please pass in an IdentityId $id'
            );
        }

        $this->decoratedEventStore->append($id, $eventStream);
    }
}

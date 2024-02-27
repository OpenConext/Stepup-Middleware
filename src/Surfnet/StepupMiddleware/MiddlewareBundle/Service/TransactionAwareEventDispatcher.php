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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\ReadModel\ProjectorInterface;
use Exception;

final class TransactionAwareEventDispatcher implements EventDispatcher
{
    private EventDispatcher $eventDispatcher;

    private DBALConnectionHelper $connectionHelper;

    public function __construct(DBALConnectionHelper $connectionHelper, EventDispatcher $eventDispatcher)
    {
        $this->connectionHelper = $connectionHelper;
        $this->eventDispatcher  = $eventDispatcher;
    }

    public function registerProjector(ProjectorInterface $projector): void
    {
        $this->eventDispatcher->registerProjector($projector);
    }

    public function dispatch(DomainEventStreamInterface $events): void
    {
        $this->connectionHelper->beginTransaction();

        try {
            $this->eventDispatcher->dispatch($events);
            $this->connectionHelper->commit();
        } catch (Exception $exception) {
            $this->connectionHelper->rollBack();

            throw $exception;
        }
    }
}

<?php

/**
 * Copyright 2021 SURFnet B.V.
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

use Broadway\EventHandling\EventBus as EventBusInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command as MiddlewareCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

final class TransactionHelper
{
    /** @var Pipeline  */
    private $pipeline;
    /** @var EventBusInterface  */
    private $eventBus;
    /** @var DBALConnectionHelper  */
    private $connection;

    public function __construct(Pipeline $pipeline, EventBusInterface $eventBus, DBALConnectionHelper $connection)
    {
        $this->pipeline = $pipeline;
        $this->eventBus = $eventBus;
        $this->connection = $connection;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function finishTransaction(): void
    {
        $this->eventBus->flush();
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function process(MiddlewareCommand $command): MiddlewareCommand
    {
        return $this->pipeline->process($command);
    }
}

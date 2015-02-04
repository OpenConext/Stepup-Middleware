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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling;

use Doctrine\DBAL\Driver\Connection;

class TransactionAwareEventFlusher
{
    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @var Connection
     */
    private $middlewareConnection;

    /**
     * @var Connection
     */
    private $gatewayConnection;

    public function __construct(
        BufferedEventBus $eventBus,
        Connection $middlewareConnection,
        Connection $gatewayConnection
    ) {
        $this->eventBus = $eventBus;
        $this->middlewareConnection = $middlewareConnection;
        $this->gatewayConnection = $gatewayConnection;
    }

    public function flush()
    {
        $this->middlewareConnection->beginTransaction();
        $this->gatewayConnection->beginTransaction();

        try {
            $this->eventBus->flush();
        } catch (\Exception $e) {
            $this->middlewareConnection->rollBack();
            $this->gatewayConnection->rollBack();

            throw $e;
        }

        $this->middlewareConnection->commit();
        $this->gatewayConnection->commit();
    }
}

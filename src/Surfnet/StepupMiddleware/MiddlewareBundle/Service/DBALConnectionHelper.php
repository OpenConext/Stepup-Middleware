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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Doctrine\DBAL\Connection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

class DBALConnectionHelper
{
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @param Connection[] $connections
     */
    public function __construct(array $connections)
    {
        foreach ($connections as $connection) {
            if (!$connection instanceof Connection) {
                throw InvalidArgumentException::invalidType('\Doctrine\DBAL\Connection', 'connection', $connection);
            }

            $this->connections[] = $connection;
        }
    }

    /**
     * Start transaction on each connection
     */
    public function beginTransaction()
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     * Commit transaction on each connection
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function commit()
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }
    }

    /**
     * Roll back the transaction on each connection
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function rollBack()
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }
    }
}

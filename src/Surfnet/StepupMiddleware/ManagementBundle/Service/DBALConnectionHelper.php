<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ManagementBundle\Service;

use Doctrine\DBAL\Connection;
use Surfnet\StepupMiddleware\ManagementBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ManagementBundle\Exception\UnknownDBALConnectionException;

class DBALConnectionHelper
{
    /**
     * @var Connection[]
     */
    private array $connections;

    /**
     * @param Connection[] $connections
     */
    public function __construct(array $connections)
    {
        foreach ($connections as $connection) {
            if (!$connection instanceof Connection) {
                throw InvalidArgumentException::invalidType(Connection::class, 'connection', $connection);
            }
        }

        $this->connections = $connections;
    }

    /**
     * Start transaction on each connection
     */
    public function beginTransaction(): void
    {
        foreach ($this->connections as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     * Commit transaction on each connection
     */
    public function commit(): void
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }
    }

    /**
     * Roll back the transaction on each connection
     */
    public function rollBack(): void
    {
        foreach ($this->connections as $connection) {
            $connection->rollBack();
        }
    }

    /**
     * @param string $connectionName
     * @return Connection
     */
    public function getConnection($connectionName)
    {
        if (!is_string($connectionName)) {
            throw InvalidArgumentException::invalidType('string', 'connectionName', $connectionName);
        }

        if (!array_key_exists($connectionName, $this->connections)) {
            throw new UnknownDBALConnectionException($connectionName);
        }

        return $this->connections[$connectionName];
    }
}

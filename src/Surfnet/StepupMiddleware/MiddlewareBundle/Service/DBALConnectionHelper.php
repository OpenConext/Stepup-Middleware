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
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\UnknownDBALConnectionException;

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
            if (!$connection->getDatabasePlatform()->supportsSavepoints()) {
                throw new InvalidArgumentException(sprintf(
                    "Connection  for database '%s' does not support nested savepoints",
                    $connection->getDatabase()
                ));
            }
            $connection->setNestTransactionsWithSavepoints(true);
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
     * @return Connection
     */
    public function getConnection(string $connectionName): Connection
    {
        if (!array_key_exists($connectionName, $this->connections)) {
            throw new UnknownDBALConnectionException($connectionName);
        }

        return $this->connections[$connectionName];
    }
}

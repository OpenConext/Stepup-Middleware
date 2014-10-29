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

namespace Surfnet\Stepup\CommandHandling;

use Doctrine\DBAL\Driver\Connection;

class TransactionAwarePipeline implements Pipeline
{
    /**
     * @var Pipeline
     */
    private $innerPipeline;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Pipeline $innerPipeline
     * @param Connection $connection
     */
    public function __construct(Pipeline $innerPipeline, Connection $connection)
    {
        $this->innerPipeline = $innerPipeline;
        $this->connection = $connection;
    }

    public function process($command)
    {
        $this->connection->beginTransaction();

        try {
            $command = $this->innerPipeline->process($command);
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $command;
    }
}

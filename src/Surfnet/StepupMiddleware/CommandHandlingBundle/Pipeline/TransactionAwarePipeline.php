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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline;

use Doctrine\DBAL\Driver\Connection;
use Psr\Log\LoggerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;

class TransactionAwarePipeline implements Pipeline
{
    /**
     * @var Pipeline
     */
    private $innerPipeline;

    /**
     * @var Connection
     */
    private $middlewareConnection;

    /**
     * @var Connection
     */
    private $gatewayConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Pipeline        $innerPipeline
     * @param Connection      $middlewareConnection
     * @param Connection      $gatewayConnection
     */
    public function __construct(
        LoggerInterface $logger,
        Pipeline $innerPipeline,
        Connection $middlewareConnection,
        Connection $gatewayConnection
    ) {
        $this->logger               = $logger;
        $this->innerPipeline        = $innerPipeline;
        $this->middlewareConnection = $middlewareConnection;
        $this->gatewayConnection    = $gatewayConnection;
    }

    public function process(Command $command)
    {
        $this->logger->debug(sprintf(
            'Starting Transaction in TransactionAwarePipeline for processing command "%s"',
            $command
        ));

        $this->middlewareConnection->beginTransaction();
        $this->gatewayConnection->beginTransaction();

        try {
            $this->logger->debug(sprintf('Requesting inner pipeline to process command "%s"', $command));

            $command = $this->innerPipeline->process($command);

            $this->logger->debug(sprintf('Inner pipeline processed command "%s", committing transaction', $command));

            $this->middlewareConnection->commit();
            $this->gatewayConnection->commit();
        } catch (\Exception $e) {
            // log at highest level if we may have a split head in the db-cluster...
            if (strpos($e->getMessage(), 'ER_UNKNOWN_COM_ERROR')) {
                $this->logger->emergency(sprintf(
                    '[!!!] Critical Database Exception while processing command "%s": "%s"',
                    $command,
                    $e->getMessage()
                ));
            } else {
                $this->logger->error(sprintf(
                    'Exception occurred while processing command "%s": "%s", rolling back transaction',
                    $command,
                    $e->getMessage()
                ));
            }

            $this->middlewareConnection->rollBack();
            $this->gatewayConnection->rollBack();

            $this->logger->debug(sprintf(
                'Transaction for command "%s" rolled back, re-throwing exception',
                $command
            ));

            throw $e;
        }

        $this->logger->debug(sprintf('Transaction committed, done processing command "%s"', $command));

        return $command;
    }
}

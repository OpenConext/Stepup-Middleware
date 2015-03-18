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

use Broadway\Domain\DomainMessage;
use Doctrine\DBAL\Connection;
use Exception;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Symfony\Component\Console\Output\OutputInterface;

class EventStreamReplayer
{
    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @var DBALEventHydrator
     */
    private $eventHydrator;

    /**
     * @var Connection
     */
    private $middlewareConnection;

    /**
     * @var Connection
     */
    private $gatewayConnection;

    /**
     * @var array
     */
    private $middlewareTables = [
        'unverified_second_factor',
        'verified_second_factor',
        'vetted_second_factor',
        'ra_second_factor',
        'identity',
        'ra',
        'raa',
        'sraa',
    ];

    /**
     * @var array
     */
    private $gatewayTables = ['second_factor'];

    public function __construct(
        BufferedEventBus $eventBus,
        DBALEventHydrator $eventHydrator,
        Connection $middlewareConnection,
        Connection $gatewayConnection
    ) {
        $this->eventBus = $eventBus;
        $this->eventHydrator = $eventHydrator;
        $this->middlewareConnection = $middlewareConnection;
        $this->gatewayConnection = $gatewayConnection;
    }

    public function replayEvents(OutputInterface $output)
    {
        $output->writeln('<info>Starting Transaction</info>');
        $this->startTransactions();

        try {
            $output->writeln('<info>Wiping READ tables</info>');
            $this->wipeReadTables($output);

            $totalEvents = $this->eventHydrator->getCount();
            $interval = 10;
            $output->writeln(sprintf(
                '<info>Found <comment>%s</comment> Events to replay in increments of </info><comment>%d</comment>',
                $totalEvents,
                $interval
            ));

            for ($count = 0; $count < $totalEvents; $count += $interval) {
                $till = min(($count + $interval), $totalEvents);

                $output->writeln(sprintf('<info>Replaying events </info><comment>%d - %d</comment>', $count, $till));

                $eventStream = $this->eventHydrator->getFromTill($interval, $count);

                $messages = [];
                $prefix = '    ';
                foreach ($eventStream->getIterator() as $event) {
                    /** @var DomainMessage $event */
                    $messages[] = $prefix . sprintf('<comment>Publishing Event "%s" for UUID "%s"</comment>', $event->getType(), $event->getId());
                }
                $output->writeln($messages);
                $this->eventBus->publish($eventStream);
                $this->eventBus->flush();
                $this->eventBus->clear();

                unset($eventStream);
            }

//            $this->rollbackTransactions();
            $this->commitTransactions();
        } catch (Exception $e) {
            $this->rollbackTransactions();

            throw $e;
        }
    }

    private function startTransactions()
    {
        $this->middlewareConnection->beginTransaction();
        $this->gatewayConnection->beginTransaction();
    }

    private function commitTransactions()
    {
        $this->middlewareConnection->commit();
        $this->gatewayConnection->commit();
    }

    private function rollbackTransactions()
    {
        $this->middlewareConnection->rollBack();
        $this->gatewayConnection->rollBack();
    }

    private function wipeReadTables(OutputInterface $output)
    {
        foreach ($this->middlewareTables as $table) {
            $rows = $this->middlewareConnection->delete($table, [1 => 1]);
            $output->writeln(sprintf(
                '<info>Deleted <comment>%d</comment> rows from table <comment>%s</comment></info>',
                $rows,
                $table
            ));
        }

        foreach ($this->gatewayTables as $table) {
            $rows = $this->gatewayConnection->delete($table, [1 => 1]);
            $output->writeln(sprintf(
                '<info>Deleted <comment>%d</comment> rows from table <comment>%s</comment></info>',
                $rows,
                $table
            ));
        }
    }
}

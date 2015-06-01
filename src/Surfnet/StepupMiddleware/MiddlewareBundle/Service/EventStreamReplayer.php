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

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Exception;
use Surfnet\Stepup\Configuration\Event\ConfigurationEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Symfony\Component\Console\Helper\ProgressBar;
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
     * @var DBALConnectionHelper
     */
    private $connectionHelper;

    /**
     * @var array
     */
    private $middlewareTables = [
        'unverified_second_factor',
        'verified_second_factor',
        'vetted_second_factor',
        'ra_second_factor',
        'identity',
        'sraa',
        'audit_log',
        'ra_listing',
        'ra_candidate'
    ];

    /**
     * @var array
     */
    private $gatewayTables = [
        'second_factor',
        'saml_entity'
    ];

    public function __construct(
        BufferedEventBus $eventBus,
        DBALEventHydrator $eventHydrator,
        DBALConnectionHelper $connectionHelper
    ) {
        $this->eventBus         = $eventBus;
        $this->eventHydrator    = $eventHydrator;
        $this->connectionHelper = $connectionHelper;
        ProgressBar::setFormatDefinition(
            'event_replay',
            "<info> %message%</info>\n"
            . ' <comment>%current%/%max%</comment> [%bar%] <comment>%percent:3s%%</comment><info>%elapsed:6s%/'
            . "%estimated:-6s%</info>\n %memory:6s%"
        );
    }

    public function replayEvents(OutputInterface $output, $increments)
    {
        $preparationProgress = new ProgressBar($output, 3);
        $preparationProgress->setFormat('event_replay');

        $preparationProgress->setMessage('Starting Transaction');
        $this->connectionHelper->beginTransaction();
        $preparationProgress->advance();

        try {
            $preparationProgress->setMessage('Removing data from Read Tables');
            $this->wipeReadTables($output);
            $preparationProgress->advance();

            $preparationProgress->setMessage('Determining amount of events to replay...');
            $totalEvents = $this->eventHydrator->getCount();

            $preparationProgress->advance();
            $defaultMessage = sprintf(
                'Found <comment>%s</comment> Events, replaying in increments of <comment>%d</comment>',
                $totalEvents,
                $increments
            );
            $preparationProgress->setMessage($defaultMessage);
            $preparationProgress->finish();

            $replayProgress = new ProgressBar($output, $totalEvents);
            $replayProgress->setFormat('event_replay');
            $replayProgress->setMessage($defaultMessage);

            for ($count = 0; $count < $totalEvents; $count += $increments) {
                /** @var DomainEventStream $eventStream */
                $eventStream = $this->eventHydrator->getFromTill($increments, $count);

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $messages = [];
                    foreach ($eventStream->getIterator() as $event) {
                        /** @var DomainMessage $event */
                        $messages[] = sprintf(
                            ' > <info>Publishing Event "<comment>%s</comment>" for UUID <comment>"%s</comment>"</info>',
                            $event->getType(),
                            $event->getId()
                        );
                    }

                    $output->writeln($messages);
                }

                $this->eventBus->publish($eventStream);
                $this->eventBus->flush();

                unset($eventStream);
                $steps = (($count + $increments < $totalEvents) ? $increments : ($totalEvents - $count));
                $replayProgress->advance($steps);
            }

            $this->connectionHelper->commit();
            $replayProgress->finish();

            $output->writeln(['', '<info>Done</info>', '']);
        } catch (Exception $e) {
            $this->connectionHelper->rollBack();
            if (isset($replayProgress)) {
                $replayProgress->setMessage(sprintf('<error>ERROR OCCURRED: "%s"</error>', $e->getMessage()));
                $replayProgress->finish();
            }

            throw $e;
        }

    }

    private function wipeReadTables(OutputInterface $output)
    {
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln('<info>Retrieving connections to wipe READ tables</info>');
        }

        $middlewareConnection = $this->connectionHelper->getConnection('middleware');
        $gatewayConnection    = $this->connectionHelper->getConnection('gateway');

        foreach ($this->middlewareTables as $table) {
            $rows = $middlewareConnection->delete($table, [1 => 1]);
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln(sprintf(
                    '<info>Deleted <comment>%d</comment> rows from table <comment>%s</comment></info>',
                    $rows,
                    $table
                ));
            }
        }

        foreach ($this->gatewayTables as $table) {
            $rows = $gatewayConnection->delete($table, [1 => 1]);
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln(sprintf(
                    '<info>Deleted <comment>%d</comment> rows from table <comment>%s</comment></info>',
                    $rows,
                    $table
                ));
            }
        }
    }
}

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
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Exception;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class EventStreamReplayer
{
    /**
     * @var string[]
     */
    private array $middlewareTables = [
        'allowed_second_factor',
        'audit_log',
        'configured_institution',
        'email_templates',
        'identity',
        'identity_self_asserted_token_options',
        'institution_authorization',
        'institution_configuration_options',
        'institution_listing',
//        'institution_with_ra_locations',
        'ra_listing',
        'ra_location',
        'ra_second_factor',
        'recovery_token',
        'second_factor_revocation',
        'sraa',
        'unverified_second_factor',
        'verified_second_factor',
        'vetted_second_factor',
        'vetting_type_hint',
        'whitelist_entry',
    ];

    /**
     * @var string[]
     */
    private array $gatewayTables = [
        'institution_configuration',
        'saml_entity',
        'second_factor',
        'whitelist_entry',
    ];

    public function __construct(
        private readonly BufferedEventBus $eventBus,
        private readonly DBALEventHydrator $eventHydrator,
        private readonly DBALConnectionHelper $connectionHelper,
    ) {
        ProgressBar::setFormatDefinition(
            'event_replay',
            "<info> %message%</info>\n"
            . ' <comment>%current%/%max%</comment> [%bar%] <comment>%percent:3s%%</comment><info>%elapsed:6s%/'
            . "%estimated:-6s%</info>\n %memory:6s%",
        );
    }

    public function replayEvents(OutputInterface $output, int $increments): void
    {
        $preparationProgress = new ProgressBar($output, 3);
        $preparationProgress->setFormat('event_replay');

        $preparationProgress->setMessage('Starting Transaction');
        $this->connectionHelper->beginTransaction();
        $preparationProgress->clear();
        $preparationProgress->advance();

        try {
            $preparationProgress->setMessage('Removing data from Read Tables');
            $preparationProgress->clear();
            $this->wipeReadTables($output);

            $preparationProgress->setMessage('Done wiping');
            $preparationProgress->clear();
            $preparationProgress->advance();

            $preparationProgress->setMessage('Determining amount of events to replay...');
            $preparationProgress->clear();
            $totalEvents = $this->eventHydrator->getCount();

            $preparationProgress->advance();

            if ($totalEvents == 0) {
                // Spaces are needed to overwrite the previous message.
                $preparationProgress->setMessage('There are no events to replay. Done.     ');
                $preparationProgress->clear();
                $preparationProgress->finish();
                return;
            } else {
                $defaultMessage = sprintf(
                    'Found <comment>%s</comment> Events, replaying in increments of <comment>%d</comment>',
                    $totalEvents,
                    $increments,
                );
                $preparationProgress->setMessage($defaultMessage);
                $preparationProgress->clear();
                $preparationProgress->finish();
            }

            $replayProgress = new ProgressBar($output, $totalEvents);
            $replayProgress->setFormat('event_replay');
            $replayProgress->setMessage($defaultMessage);

            for ($count = 0; $count < $totalEvents; $count += $increments) {
                $eventStream = $this->eventHydrator->getFromTill($increments, $count);

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $messages = [];
                    foreach ($eventStream->getIterator() as $event) {
                        /** @var DomainMessage $event */
                        $messages[] = sprintf(
                            ' > <info>Publishing Event <comment>%s</comment> "<comment>%s</comment>" for UUID <comment>"%s</comment>"</info>',
                            $event->getRecordedOn()->toString(),
                            $event->getType(),
                            $event->getId(),
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
        } catch (Throwable $e) {
            echo $e->getMessage()."\n";

            $this->connectionHelper->rollBack();
            if (isset($replayProgress)) {
                $replayProgress->setMessage(sprintf('<error>ERROR OCCURRED: "%s"</error>', $e->getMessage()));
                $replayProgress->finish();
            }

            throw $e;
        }
    }

    /**
     * @throws InvalidArgumentException|\Doctrine\DBAL\Exception
     */
    private function wipeReadTables(OutputInterface $output): void
    {
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln('<info>Retrieving connections to wipe READ tables</info>');
        }

        $middlewareConnection = $this->connectionHelper->getConnection('middleware');
        $gatewayConnection = $this->connectionHelper->getConnection('gateway');

        $middlewareDatabaseName = $middlewareConnection->getDatabase();
        $gatewayDatabaseName = $gatewayConnection->getDatabase();

        foreach ($this->middlewareTables as $table) {
            $rows = $middlewareConnection->delete($table, [1 => 1]);
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln(
                    sprintf(
                        '<info>Deleted <comment>%d</comment> rows from table <comment>%s.%s</comment></info>',
                        $rows,
                        $middlewareDatabaseName,
                        $table,
                    ),
                );
            }
        }

        foreach ($this->gatewayTables as $table) {
            $rows = $gatewayConnection->delete($table, [1 => 1]);
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln(
                    sprintf(
                        '<info>Deleted <comment>%d</comment> rows from table <comment>%s.%s</comment></info>',
                        $rows,
                        $gatewayDatabaseName,
                        $table,
                    ),
                );
            }
        }
    }
}

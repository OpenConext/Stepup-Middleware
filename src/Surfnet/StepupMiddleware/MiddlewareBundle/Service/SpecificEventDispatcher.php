<?php

/**
 * Copyright 2017 SURFnet B.V.
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
use Exception;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;
use Symfony\Component\Console\Output\OutputInterface;

final class SpecificEventDispatcher
{
    /**
     * @var DBALConnectionHelper
     */
    private $connectionHelper;

    /**
     * @var DBALEventHydrator
     */
    private $eventHydrator;

    public function __construct(DBALConnectionHelper $connectionHelper, DBALEventHydrator $eventHydrator)
    {
        $this->connectionHelper = $connectionHelper;
        $this->eventHydrator = $eventHydrator;
    }

    public function dispatchEventsForProjectors(
        EventCollection $eventCollection,
        ProjectorCollection $projectorCollection,
        OutputInterface $output
    ) {
        $output->writeln('<info>Starting event dispatch</info>');
        $this->connectionHelper->beginTransaction();

        try {
            $output->writeln('<info>Hydrating selected events</info>');
            $events = $this->eventHydrator->getEventsFrom($eventCollection);

            $output->writeln('<info>Attempting to handle selected events with selected projectors:</info>');

            /** @var DomainMessage $event */
            foreach ($events as $event) {
                $output->writeln(sprintf(' <info>> Event</info> "%s"', $event->getType()));

                foreach ($projectorCollection as $projectorName => $projector) {
                    $output->writeln(sprintf('   <info>> Projector</info> "%s"', $projectorName));
                    $projector->handle($event);
                }
            }

            $output->writeln('Event dispatch successful');
            $this->connectionHelper->commit();
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>Event dispatch failed: %s</error>', $exception->getMessage()));
            $this->connectionHelper->rollBack();

            throw $exception;
        }
    }
}

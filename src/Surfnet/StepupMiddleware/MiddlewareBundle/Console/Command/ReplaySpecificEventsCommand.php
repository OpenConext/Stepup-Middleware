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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\PastEventsService;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TransactionAwareEventDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'stepup:event:replay',
    description: 'replay specified events for specified projectors'
)]
class ReplaySpecificEventsCommand extends Command
{
    public const OPTION_LIST_EVENTS = 'list-events';
    public const OPTION_LIST_PROJECTORS = 'list-projectors';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_LIST_EVENTS,
                null,
                InputOption::VALUE_NONE,
                'List all events available to replay',
            )
            ->addOption(
                self::OPTION_LIST_PROJECTORS,
                null,
                InputOption::VALUE_NONE,
                'List all projectors available for which events can be replayed',
            );
    }

    public function __construct(
        private readonly EventCollection $collection,
        private readonly ProjectorCollection $projectorCollection,
        private readonly PastEventsService $pastEventsService,
        private readonly TransactionAwareEventDispatcher $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $availableEvents = $this->collection->getEventNames();
        $availableProjectors = $this->projectorCollection->getProjectorNames();

        if ($input->getOption(self::OPTION_LIST_EVENTS)) {
            $output->writeln('<info>The following events can be replayed:</info>');
            $output->writeln($availableEvents === [] ? 'None.' : $availableEvents);

            return 0;
        }

        if ($input->getOption(self::OPTION_LIST_PROJECTORS)) {
            $output->writeln('<info>Events can be replayed for the following projectors:</info>');
            $output->writeln($availableProjectors === [] ? 'None.' : $availableProjectors);

            return 0;
        }

        if ($availableProjectors === []) {
            $output->writeln('<error>There are no projectors configured to reply events for</error>');

            return 1;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $selectEventsQuestion = new ChoiceQuestion(
            'Which events would you like to replay? Please supply a comma-separated list of numbers.',
            $availableEvents,
        );
        $selectEventsQuestion->setMultiselect(true);

        $chosenEvents = $questionHelper->ask($input, $output, $selectEventsQuestion);
        $eventSelection = $this->collection->select($chosenEvents);

        $selectProjectorsQuestion = new ChoiceQuestion(
            'For which projectors would you like to replay the selected events? '
            . 'Please supply a comma-separated list of numbers.',
            $availableProjectors,
        );
        $selectProjectorsQuestion->setMultiselect(true);

        $chosenProjectors = $questionHelper->ask($input, $output, $selectProjectorsQuestion);
        $projectorSelection = $this->projectorCollection->selectByNames($chosenProjectors);

        $events = $this->pastEventsService->findEventsBy($eventSelection);

        $output->writeln('<info>Registering projectors</info>');
        foreach ($projectorSelection as $projector) {
            $this->eventDispatcher->registerProjector($projector);
        }

        $output->writeln('<info>Dispatching events</info>');
        $this->eventDispatcher->dispatch($events);
        return 0;
    }
}

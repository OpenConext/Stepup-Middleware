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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ReplaySpecificEventsCommand extends Command
{
    const OPTION_LIST_EVENTS = 'list-events';
    const OPTION_LIST_PROJECTORS = 'list-projectors';

    private EventCollection $collection;

    private PastEventsService $pastEventsService;

    private TransactionAwareEventDispatcher $eventDispatcher;
    private ProjectorCollection $projectorCollection;

    protected function configure()
    {
        $this
            ->setName('stepup:event:replay')
            ->setDescription('replay specified events for specified projectors')
            ->addOption(
                self::OPTION_LIST_EVENTS,
                null,
                InputOption::VALUE_NONE,
                'List all events available to replay'
            )
            ->addOption(
                self::OPTION_LIST_PROJECTORS,
                null,
                InputOption::VALUE_NONE,
                'List all projectors available for which events can be replayed'
            );
    }

    public function __construct(
        EventCollection $collection,
        ProjectorCollection $projectorCollection,
        PastEventsService $pastEventsService,
        TransactionAwareEventDispatcher $eventDispatcher
    ) {
        $this->collection = $collection;
        $this->projectorCollection = $projectorCollection;
        $this->pastEventsService = $pastEventsService;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableEvents     = $this->collection->getEventNames();
        $availableProjectors = $this->projectorCollection->getProjectorNames();

        if ($input->getOption(self::OPTION_LIST_EVENTS)) {
            $output->writeln('<info>The following events can be replayed:</info>');
            $output->writeln(empty($availableEvents) ? 'None.' : $availableEvents);

            return;
        }

        if ($input->getOption(self::OPTION_LIST_PROJECTORS)) {
            $output->writeln('<info>Events can be replayed for the following projectors:</info>');
            $output->writeln($availableProjectors === [] ? 'None.' : $availableProjectors);

            return;
        }

        if ($availableProjectors === []) {
            $output->writeln('<error>There are no projectors configured to reply events for</error>');

            return;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $selectEventsQuestion = new ChoiceQuestion(
            'Which events would you like to replay? Please supply a comma-separated list of numbers.',
            $availableEvents
        );
        $selectEventsQuestion->setMultiselect(true);

        $chosenEvents   = $questionHelper->ask($input, $output, $selectEventsQuestion);
        $eventSelection = $this->collection->select($chosenEvents);

        $selectProjectorsQuestion = new ChoiceQuestion(
            'For which projectors would you like to replay the selected events? '
            . 'Please supply a comma-separated list of numbers.',
            $availableProjectors
        );
        $selectProjectorsQuestion->setMultiselect(true);

        $chosenProjectors   = $questionHelper->ask($input, $output, $selectProjectorsQuestion);
        $projectorSelection = $this->projectorCollection->selectByNames($chosenProjectors);

        $events = $this->pastEventsService->findEventsBy($eventSelection);

        $output->writeln('<info>Registering projectors</info>');
        foreach ($projectorSelection as $projector) {
            $this->eventDispatcher->registerProjector($projector);
        }

        $output->writeln('<info>Dispatching events</info>');
        $this->eventDispatcher->dispatch($events);
    }
}

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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ReplaySpecificEventsCommand extends ContainerAwareCommand
{
    const OPTION_LIST_EVENTS = 'list-events';

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
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $eventCollection = $container->get('middleware.event_replay.event_collection');

        if ($input->getOption(self::OPTION_LIST_EVENTS)) {
            $output->writeln('<info>The following events can be replayed:</info>');
            $output->writeln(iterator_to_array($eventCollection));

            return;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $selectEventsQuestion = new ChoiceQuestion(
            'Which events would you like to replay? Please supply a comma-separated list of numbers.',
            iterator_to_array($eventCollection)
        );
        $selectEventsQuestion->setMultiselect(true);

        $chosenEvents = $questionHelper->ask($input, $output, $selectEventsQuestion);
        $eventCollection->select($chosenEvents);
    }
}

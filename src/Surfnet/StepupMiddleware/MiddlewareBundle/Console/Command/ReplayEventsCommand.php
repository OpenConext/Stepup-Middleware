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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Surfnet\StepupMiddleware\MiddlewareBundle\Service\EventStreamReplayer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'middleware:event:replay',
    description: 'Wipes all read models and repopulates the tables from the event store. Use the 
                --no-interaction option to perform the event replay without the additional confirmation question.'
)]
class ReplayEventsCommand extends Command
{
    public function __construct(
        private readonly EventStreamReplayer $replayer,
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
             ->addOption(
                 'increments',
                 'i',
                 InputOption::VALUE_REQUIRED,
                 'The amount of events that are replayed at once (repeated until all events are replayed)',
                 1000,
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        // Be careful, when using the no-interaction option you will not get the confirmation question
        $noInteraction = $input->getOption('no-interaction');

        if (!in_array($this->environment, ['dev_event_replay', 'prod_event_replay', 'smoketest_event_replay'])) {
            $output->writeln(
                $formatter->formatBlock(
                    [
                        '',
                        'This command may only be executed using env "dev_event_replay", "prod_event_replay", or 
                    "smoketest_event_replay"',
                        '',
                    ],
                    'error',
                ),
            );

            return 1;
        }

        /** @var QuestionHelper $interrogator */
        $interrogator = $this->getHelper('question');
        if ($this->environment === 'prod_event_replay') {
            $wantToRunOnProd = new ConfirmationQuestion(
                '<question>You have selected to run this on production. Have you disabled all access to the production '
                . 'environment? (y/N)</question>',
                false,
            );

            if (!$interrogator->ask($input, $output, $wantToRunOnProd)) {
                $output->writeln('<comment>Not starting the replay</comment>');

                return 1;
            }
        }

        $increments = (int)$input->getOption('increments');
        if ($increments < 1) {
            $output->writeln(
                $formatter->formatBlock(
                    sprintf('Increments must be a positive integer, "%s" given', $input->getOption('increments')),
                    'error',
                ),
            );

            return 1;
        }

        if (!$noInteraction) {
            $output->writeln(['', $formatter->formatBlock(['', 'WARNING!!!!', ''], 'error'), '']);

            $question = <<<QUESTION
You are about to WIPE all read data and recreate all data based on the events present, in steps of %d.

  <%s>>> This can take a while and should not be interrupted <<</%s>

Are you sure you wish to continue? (y/N)
QUESTION;

            $question = sprintf($question, $increments, 'error', 'error');
            $areYouSure = new ConfirmationQuestion(sprintf("<question>%s</question>\n", $question), false);
            if (!$interrogator->ask($input, $output, $areYouSure)) {
                $output->writeln('<comment>Replay cancelled!</comment>');

                return 1;
            }
        }

        $output->writeln(['', $formatter->formatBlock('Starting Event Replay', 'info')]);
        $output->writeln(
            $formatter->formatBlock(' >> If it is interrupted it must be rerun till completed', 'comment'),
        );

        $this->replayer->replayEvents($output, $increments);
        return 0;
    }
}

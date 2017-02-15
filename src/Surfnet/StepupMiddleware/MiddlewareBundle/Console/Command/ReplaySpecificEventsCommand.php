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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReplaySpecificEventsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('middleware:event:specific_replay')
            ->setDescription('replay specified events for specified projectors');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        /** @var QuestionHelper $interrogator */
        $interrogator = $this->getHelper('question');

        $output->writeln(['', $formatter->formatBlock([
            '',
            'WARNING!!!!',
            '',
            'You are about to WIPE all read data and recreate all data based on chosen events and projectors.',
            '',
            'This can take a while and should not be interrupted',
            ''
        ], 'error'), '']);
        $question = "Are you sure you wish to continue? (y/N)";

        $question = sprintf($question, 'error', 'error');
        $areYouSure = new ConfirmationQuestion(sprintf("<question>%s</question>\n", $question), false);
        if (!$interrogator->ask($input, $output, $areYouSure)) {
            $output->writeln('<comment>Replay cancelled!</comment>');

            return;
        }

        $output->writeln(['',$formatter->formatBlock('Starting Event Replay', 'info')]);
        $output->writeln(
            $formatter->formatBlock(' >> If it is interrupted it must be rerun till completed', 'comment')
        );

        // ensures the progressbar doesn't overwrite the messages above
        $output->writeln(['','',''], 'info');
    }
}

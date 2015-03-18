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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

class ReplayEventsCommand extends Command
{
    protected function configure()
    {
        $this->setName('middleware:event:replay');
        $this->setDescription('wipes all read models and repopulates the tables from the event store');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->getApplication()->getKernel();
        $environment = $kernel->getEnvironment();

        if (!in_array($environment, ['dev_event_replay', 'prod_event_replay'])) {
            $output->writeln([
                '<error>This command may only be executed using "--env=dev_event_replay" or "--env=prod_event_replay" '
                . '</error>'
            ]);
            return;
        }

        /** @var QuestionHelper $interrogator */
        $interrogator = $this->getHelper('question');
        if ($environment === "prod_event_replay") {
            $wantToRunOnProd = new ConfirmationQuestion(
                '<question>You have selected to run this on production. Have you disabled all access to the production '
                . 'environment? (y/N)</question>',
                false
            );

            if (!$interrogator->ask($input, $output, $wantToRunOnProd)) {
                $output->writeln('<comment>Not starting the replay</comment>');
                return;
            }
        }

        $output->writeln(['','<error>WARNING!!!!</error>', '']);

        $areYouSure = new ConfirmationQuestion(
            '<question>You are about to WIPE all read data and recreate all data from scratch. This can take a while '
            . 'and may NOT be interrupted! Are you sure you wish to continue? (y/n)</question>',
            false
        );
        if (!$interrogator->ask($input, $output, $areYouSure)) {
            $output->writeln('<comment>Replay cancelled!</comment>');

            return;
        }

        $output->writeln(['','<info>Starting Replay of Events</info> <comment>DO NOT INTERRUPT THIS!</comment>']);

        /** @var Container $container */
        $container = $kernel->getContainer();

        $replayer = $container->get('middleware.event_replay.event_stream_replayer');

        $replayer->replayEvents($output);
    }
}

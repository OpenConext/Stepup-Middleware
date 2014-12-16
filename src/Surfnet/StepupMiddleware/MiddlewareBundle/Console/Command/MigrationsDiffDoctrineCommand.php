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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class MigrationsDiffDoctrineCommand extends Command
{
    protected function configure()
    {
        $this->setName('middleware:migrations:diff');
        $this->setDescription('Performs a mapping/database diff using the correct entity manager');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['<info>Generating diff for Middleware...</info>', '']);

        // Cannae run migrations command twice in the same process: registers the migration classes twice with
        // Doctrine\DBAL\Migrations\Version.
        ProcessBuilder::create(
            ['app/console', 'doc:mig:diff', '--em=middleware', '--filter-expression=~^(?!event_stream).*$~']
        )
            ->getProcess()
            ->run(function ($type, $data) use ($output) {
                $output->write($data);
            });

        $output->writeln(['<info>Generating diff for Gateway...</info>', '']);

        ProcessBuilder::create(['app/console', 'doc:mig:diff', '--em=gateway'])
            ->getProcess()
            ->run(function ($type, $data) use ($output) {
                $output->write($data);
            });
    }
}

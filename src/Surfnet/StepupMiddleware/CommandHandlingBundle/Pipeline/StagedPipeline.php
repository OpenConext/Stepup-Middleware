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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline;

use Psr\Log\LoggerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;

class StagedPipeline implements Pipeline
{
    /**
     * @var Stage[]
     */
    private $stages = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(Command $command)
    {
        $this->logger->debug(sprintf('Processing "%s"', $command));

        foreach ($this->stages as $stage) {
            $this->logger->debug(sprintf('Invoking stage "%s" for %s', get_class($stage), $command));

            $command = $stage->process($command);

            $this->logger->debug(sprintf('Stage "%s" finished processing %s', get_class($stage), $command));
        }

        $this->logger->debug(sprintf('Done processing %s in StagedPipeline', $command));

        return $command;
    }

    public function addStage(Stage $stage)
    {
        $this->stages[] = $stage;
    }
}

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
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;

class EventDispatchingStage implements Stage
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BufferedEventBus $bufferedEventBus,
    ) {
    }

    public function process(Command $command): Command
    {
        $this->logger->debug(sprintf('Dispatching Events for "%s"', $command));

        $this->bufferedEventBus->flush();

        $this->logger->debug(sprintf('All events for "%s" have been dispatched', $command));

        return $command;
    }
}

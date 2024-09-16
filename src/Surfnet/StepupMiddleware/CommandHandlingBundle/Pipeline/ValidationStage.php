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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception\InvalidCommandException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationStage implements Stage
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function process(AbstractCommand $command): AbstractCommand
    {
        $this->logger->debug(sprintf('Processing validation for "%s"', $command));

        $violations = $this->validator->validate($command);

        if (count($violations) > 0) {
            $this->logger->debug(sprintf('Command "%s" has validation violations', $command));

            throw InvalidCommandException::createFromViolations($violations);
        }

        $this->logger->debug(sprintf('Command "%s" is valid', $command));

        return $command;
    }
}

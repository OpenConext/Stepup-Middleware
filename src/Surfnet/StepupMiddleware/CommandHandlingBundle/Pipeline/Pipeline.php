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

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;

/**
 * Describes a linear structure in which commands are processed. For example, authorisation may be checked and
 * validation may take place before actually handling a command.
 */
interface Pipeline
{
    /**
     * @return Command
     */
    public function process(Command $command);
}

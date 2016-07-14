<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Dispatcher;

use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Api\InstitutionConfigurationCreationService;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

final class CreateInstitutionConfigurationCommandDispatcher implements InstitutionConfigurationCreationService
{
    /**
     * @var Pipeline
     */
    private $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function createConfigurationFor(Institution $institution)
    {
        $command              = new CreateInstitutionConfigurationCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $institution;

        $this->pipeline->process($command);
    }
}

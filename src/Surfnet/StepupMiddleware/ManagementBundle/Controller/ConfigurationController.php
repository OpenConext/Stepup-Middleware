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

namespace Surfnet\StepupMiddleware\ManagementBundle\Controller;

use DateTime;
use Ramsey\Uuid\Uuid;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends AbstractController
{
    public function __construct(
        private readonly TransactionAwarePipeline $pipeline,
    ) {
    }

    public function update(Request $request): JsonResponse
    {
        $command = new UpdateConfigurationCommand();
        $command->configuration = $request->getContent();
        $command->UUID = (string)Uuid::uuid4();

        return $this->handleCommand($request, $command);
    }

    private function handleCommand(Request $request, AbstractCommand $command): JsonResponse
    {
        $this->pipeline->process($command);

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');

        return new JsonResponse([
            'status' => 'OK',
            'processed_by' => $serverName,
            'applied_at' => (new DateTime())->format(DateTime::ISO8601),
        ]);
    }
}

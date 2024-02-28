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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends AbstractController
{
    /**
     * @return TransactionAwarePipeline
     */
    private TransactionAwarePipeline $pipeline;

    public function __construct(TransactionAwarePipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function updateAction(Request $request)
    {
        $command                = new UpdateConfigurationCommand();
        $command->UUID          = (string) Uuid::uuid4();
        $command->configuration = $request->getContent();

        return $this->handleCommand($request, $command);
    }

    /**
     * @param Request $request
     * @param Command $command
     * @return JsonResponse
     */
    private function handleCommand(Request $request, Command $command): JsonResponse
    {
        $this->pipeline->process($command);

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response   = new JsonResponse([
            'status'       => 'OK',
            'processed_by' => $serverName,
            'applied_at'   => (new DateTime())->format(DateTime::ISO8601)
        ]);

        return $response;
    }
}

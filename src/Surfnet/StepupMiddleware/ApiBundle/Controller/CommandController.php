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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CommandController extends Controller
{
    public function handleAction(Command $command, Request $request)
    {
        /** @var Pipeline $pipeline */
        $pipeline = $this->get('pipeline');
        $command = $pipeline->process($command);

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response = new JsonResponse(['command' => $command->UUID, 'processed_by' => $serverName]);

        return $response;
    }
}

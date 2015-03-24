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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CommandController extends Controller
{
    public function handleAction(Command $command, Request $request)
    {
        /** @var \Monolog\Logger $logger */
        $logger = $this->get('logger');

        $logger->notice(sprintf('Received request to process Command "%s"', $command));

        /** @var Pipeline $pipeline */
        $pipeline = $this->get('pipeline');

        try {
            $command = $pipeline->process($command);
        } catch (ForbiddenException $e) {
            throw new AccessDeniedHttpException(
                sprintf('Processing of command "%s" is forbidden for this client', $command),
                $e
            );
        }

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response = new JsonResponse(['command' => $command->UUID, 'processed_by' => $serverName]);

        $logger->notice(sprintf('Command "%s" has been successfully processed', $command));

        return $response;
    }
}

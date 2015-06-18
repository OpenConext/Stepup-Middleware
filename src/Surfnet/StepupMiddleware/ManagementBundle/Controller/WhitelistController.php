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
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AddToWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RemoveFromWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ReplaceWhitelistCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WhitelistController extends Controller
{
    public function replaceWhitelistAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $command               = new ReplaceWhitelistCommand();
        $command->UUID         = (string) Uuid::uuid4();
        $command->institutions = $this->getInstitutionsFromBody($request);

        return $this->handleCommand($request, $command);
    }

    public function addToWhitelistAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $command                        = new AddToWhitelistCommand();
        $command->UUID                  = (string) Uuid::uuid4();
        $command->institutionsToBeAdded = $this->getInstitutionsFromBody($request);

        return $this->handleCommand($request, $command);
    }

    public function removeFromWhitelistAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $command                          = new RemoveFromWhitelistCommand();
        $command->UUID                    = (string) Uuid::uuid4();
        $command->institutionsToBeRemoved = $this->getInstitutionsFromBody($request);

        return $this->handleCommand($request, $command);
    }

    public function showWhitelistAction()
    {
        $entries = $this->get('surfnet_stepup_middleware_api.service.whitelist_entry')->getAllEntries();

        return new JsonResponse(['institutions' => $entries->getValues()]);
    }

    /**
     * @param Request $request
     * @param Command $command
     * @return JsonResponse
     */
    private function handleCommand(Request $request, Command $command)
    {
        /** @var \Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline $pipeline */
        $pipeline = $this->get('pipeline');

        try {
            $pipeline->process($command);
        } catch (ForbiddenException $e) {
            throw new AccessDeniedHttpException(
                sprintf('Processing of command "%s" is forbidden for this client', $command),
                $e
            );
        }

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response   = new JsonResponse([
            'status'       => 'OK',
            'processed_by' => $serverName,
            'applied_at'   => (new DateTime())->format(DateTime::ISO8601),
        ]);

        return $response;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getInstitutionsFromBody(Request $request)
    {
        $decoded = \GuzzleHttp\json_decode($request->getContent(), true);

        if (!isset($decoded['institutions']) || !is_array($decoded['institutions'])) {
            throw new BadRequestHttpException(
                'Request must contain json object with property "institutions" containing an array of institutions'
            );
        }

        return $decoded['institutions'];
    }
}

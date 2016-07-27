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

namespace Surfnet\StepupMiddleware\ManagementBundle\Controller;

use DateTime;
use GuzzleHttp;
use Liip\FunctionalTestBundle\Validator\DataCollectingValidator;
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Constraints\ValidReconfigureInstitutionsRequest;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class InstitutionConfigurationController extends Controller
{
    public function reconfigureAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $configuration = GuzzleHttp\json_decode($request->getContent(), true);

        $violations = $this->getValidator()->validate($configuration, new ValidReconfigureInstitutionsRequest());
        if ($violations->count() > 0) {
            throw BadCommandRequestException::withViolations(
                'Invalid reconfigure institutions request',
                $violations
            );
        }

        $commands = [];
        foreach ($configuration as $institution => $options) {
            $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
            $command->UUID                            = (string) Uuid::uuid4();
            $command->institution                     = $institution;
            $command->useRaLocationsOption            = $options['use_ra_locations'];
            $command->showRaaContactInformationOption = $options['show_raa_contact_information'];

            $commands[] = $command;
        }

        if (empty($commands)) {
            $this->getLogger()->notice('Institution configuration will not be reconfigured: no commands to execute.');
        }

        $pipeline = $this->getPipeline();
        foreach ($commands as $command) {
            $this->handleCommand($pipeline, $command);
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
     * @param Pipeline $pipeline
     * @param Command $command
     * @return JsonResponse
     */
    private function handleCommand(Pipeline $pipeline, Command $command)
    {
        try {
            $pipeline->process($command);
        } catch (ForbiddenException $e) {
            throw new AccessDeniedHttpException(
                sprintf('Processing of command "%s" is forbidden for this client', $command),
                $e
            );
        }
    }

    /**
     * @return DataCollectingValidator
     */
    private function getValidator()
    {
        return $this->container->get('validator');
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->get('logger');
    }

    /**
     * @return TransactionAwarePipeline
     */
    private function getPipeline()
    {
        return $this->get('pipeline');
    }
}

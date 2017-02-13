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
use Exception;
use Liip\FunctionalTestBundle\Validator\DataCollectingValidator;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Helper\JsonHelper;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\ManagementBundle\Service\DBALConnectionHelper;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Constraints\ValidReconfigureInstitutionsRequest;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class InstitutionConfigurationController extends Controller
{
    public function showAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $institutionConfigurationOptions = $this->getInstitutionConfigurationOptionsService()
            ->findAllInstitutionConfigurationOptions();

        $overview = [];
        foreach ($institutionConfigurationOptions as $options) {
            $overview[$options->institution->getInstitution()] = [
                'use_ra_locations' => $options->useRaLocationsOption,
                'show_raa_contact_information' => $options->showRaaContactInformationOption,
            ];
        }

        return new JsonResponse($overview);
    }

    public function reconfigureAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $configuration = JsonHelper::decode($request->getContent());

        $violations = $this->getValidator()->validate($configuration, new ValidReconfigureInstitutionsRequest());
        if ($violations->count() > 0) {
            throw BadCommandRequestException::withViolations('Invalid reconfigure institutions request', $violations);
        }

        if (empty($configuration)) {
            $this->getLogger()->notice(sprintf('No institutions to reconfigure: empty configuration received'));

            return new JsonResponse([
                'status'       => 'OK',
                'processed_by' =>  $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR'),
                'applied_at'   => (new DateTime())->format(DateTime::ISO8601),
            ]);
        }

        $commands = [];
        foreach ($configuration as $institution => $options) {
            $command                                  = new ReconfigureInstitutionConfigurationOptionsCommand();
            $command->UUID                            = (string) Uuid::uuid4();
            $command->institution                     = $institution;
            $command->useRaLocationsOption            = $options['use_ra_locations'];
            $command->showRaaContactInformationOption = $options['show_raa_contact_information'];
            $command->allowedSecondFactors            = $options['allowed_second_factors'];

            $commands[] = $command;
        }

        $this->getLogger()->notice(
            sprintf('Executing %s reconfigure institution configuration options commands', count($commands))
        );

        $this->handleCommands($commands);

        return new JsonResponse([
            'status'       => 'OK',
            'processed_by' =>  $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR'),
            'applied_at'   => (new DateTime())->format(DateTime::ISO8601),
        ]);
    }

    /**
     * @param Command[] $commands
     * @throws Exception
     */
    private function handleCommands(array $commands)
    {
        $pipeline         = $this->getPipeline();
        $connectionHelper = $this->getConnectionHelper();

        $connectionHelper->beginTransaction();

        foreach ($commands as $command) {
            try {
                $pipeline->process($command);
            } catch (ForbiddenException $e) {
                $connectionHelper->rollBack();

                throw new AccessDeniedHttpException(
                    sprintf('Processing of command "%s" is forbidden for this client', $command),
                    $e
                );
            } catch (Exception $exception) {
                $connectionHelper->rollBack();

                throw $exception;
            }
        }

        $connectionHelper->commit();
    }

    /**
     * @return InstitutionConfigurationOptionsService
     */
    private function getInstitutionConfigurationOptionsService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.institution_configuration_options');
    }

    /**
     * @return DataCollectingValidator
     */
    private function getValidator()
    {
        return $this->get('validator');
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->get('logger');
    }

    /**
     * @return Pipeline
     */
    private function getPipeline()
    {
        return $this->get('pipeline');
    }

    /**
     * @return DBALConnectionHelper
     */
    private function getConnectionHelper()
    {
        return $this->get('surfnet_stepup_middleware_management.dbal_connection_helper');
    }
}

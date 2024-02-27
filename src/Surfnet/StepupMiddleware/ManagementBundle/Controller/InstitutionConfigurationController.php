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
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Helper\JsonHelper;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Surfnet\StepupMiddleware\ManagementBundle\Service\DBALConnectionHelper;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Constraints\ValidReconfigureInstitutionsRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class InstitutionConfigurationController extends AbstractController
{
    /**
     * @return InstitutionConfigurationOptionsService
     */
    private InstitutionConfigurationOptionsService $institutionConfigurationOptionsService;

    /**
     * @return InstitutionAuthorizationService
     */
    private InstitutionAuthorizationService $institutionAuthorizationService;

    /**
     * @return ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @return AllowedSecondFactorListService
     */
    private AllowedSecondFactorListService $allowedSecondFactorListService;

    /**
     * @return LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @return TransactionAwarePipeline
     */
    private TransactionAwarePipeline $pipeline;

    private DBALConnectionHelper $connectionHelper;

    public function __construct(
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        InstitutionAuthorizationService $institutionAuthorizationService,
        ValidatorInterface $dataCollectingValidator,
        AllowedSecondFactorListService $allowedSecondFactorListService,
        LoggerInterface $logger,
        TransactionAwarePipeline $pipeline,
        DBALConnectionHelper $dbalConnectionHelper
    ) {
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->institutionAuthorizationService = $institutionAuthorizationService;
        $this->validator = $dataCollectingValidator;
        $this->allowedSecondFactorListService = $allowedSecondFactorListService;
        $this->logger = $logger;
        $this->pipeline = $pipeline;
        $this->connectionHelper = $dbalConnectionHelper;
    }

    public function showAction(): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findAllInstitutionConfigurationOptions();

        $allowedSecondFactorMap = $this->allowedSecondFactorListService->getAllowedSecondFactorMap();

        $overview = [];
        foreach ($institutionConfigurationOptions as $options) {
            // Load the numberOfTokensPerIdentity from the institution config options service
            $numberOfTokensPerIdentity = $this->institutionConfigurationOptionsService
                ->getMaxNumberOfTokensFor(new Institution($options->institution->getInstitution()));

            // Get the authorization options for this institution
            $institutionConfigurationOptionsMap = $this->institutionAuthorizationService
                ->findAuthorizationsFor($options->institution);

            $overview[$options->institution->getInstitution()] = [
                'use_ra_locations' => $options->useRaLocationsOption,
                'show_raa_contact_information' => $options->showRaaContactInformationOption,
                'verify_email' => $options->verifyEmailOption,
                'self_vet' => $options->selfVetOption,
                'sso_on_2fa' => $options->ssoOn2faOption,
                'allow_self_asserted_tokens' => $options->selfAssertedTokensOption,
                'number_of_tokens_per_identity' => $numberOfTokensPerIdentity,
                'allowed_second_factors' => $allowedSecondFactorMap->getAllowedSecondFactorListFor(
                    $options->institution
                ),
                'use_ra' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::useRa())->jsonSerialize(),
                'use_raa' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::useRaa())->jsonSerialize(),
                'select_raa' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::selectRaa())->jsonSerialize(),
            ];
        }

        return new JsonResponse($overview);
    }

    public function reconfigureAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $configuration = JsonHelper::decode($request->getContent());

        $violations = $this->validator->validate($configuration, new ValidReconfigureInstitutionsRequest());
        if ($violations->count() > 0) {
            throw BadCommandRequestException::withViolations('Invalid reconfigure institutions request', $violations);
        }

        if (empty($configuration)) {
            $this->logger->notice(sprintf('No institutions to reconfigure: empty configuration received'));

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
            $command->verifyEmailOption               = $options['verify_email'];
            $command->numberOfTokensPerIdentityOption = $options['number_of_tokens_per_identity'];
            $command->allowedSecondFactors            = $options['allowed_second_factors'];
            // The useRa, useRaa and selectRaa options are optional
            $command->useRaOption = isset($options['use_ra']) ? $options['use_ra'] : null;
            $command->useRaaOption = isset($options['use_raa']) ? $options['use_raa'] : null;
            $command->selectRaaOption = isset($options['select_raa']) ? $options['select_raa'] : null;
            // So are sso_on_2fa and the allow_self_asserted_tokens options
            $command->selfVetOption = $options['self_vet'] ?? null;
            $command->ssoOn2faOption = $options['sso_on_2fa'] ?? null;
            $command->selfAssertedTokensOption = $options['allow_self_asserted_tokens'] ?? null;

            $commands[] = $command;
        }

        $this->logger->notice(
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
    private function handleCommands(array $commands): void
    {
        $connectionHelper = $this->connectionHelper;

        $connectionHelper->beginTransaction();

        foreach ($commands as $command) {
            try {
                $this->pipeline->process($command);
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
}

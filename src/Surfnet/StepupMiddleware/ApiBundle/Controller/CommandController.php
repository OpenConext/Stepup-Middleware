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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventSourcing\MetadataEnricher;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CommandController extends Controller
{
    public function handleAction(Command $command, Metadata $metadata, Request $request)
    {
        /** @var \Monolog\Logger $logger */
        $logger = $this->get('logger');
        $logger->notice(sprintf('Received request to process Command "%s"', $command));

        /** @var MetadataEnricher $metadataEnricher */
        $metadataEnricher = $this->get('surfnet_stepup_middleware_command_handling.metadata_enricher.actor');
        $metadataEnricher->setMetadata($metadata);

        if ($this->get('security.authorization_checker')->isGranted('ROLE_MANAGEMENT')) {
            $logger->notice('Command sent through Management API, not enforcing Whitelist');
        } else {
            $logger->notice('Ensuring that the actor institution is on the whitelist, or the actor is SRAA');

            $institution = $this->resolveInstitution($command, $metadata);
            $this->assertCommandMayBeProcessedOnBehalfOf($institution, $metadata->actorId);
        }

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

    /**
     * @param Command  $command
     * @param Metadata $metadata
     * @return string
     */
    private function resolveInstitution(Command $command, Metadata $metadata)
    {
        if ($metadata->actorInstitution) {
            return $metadata->actorInstitution;
        }

        // the createIdentityCommand is used to create an Identity for a new user, not logged in yet,
        // thus there is not Metadata::actorInstitution
        if ($command instanceof CreateIdentityCommand) {
            return $command->institution;
        }

        // conservative, if we cannot determine an institution, deny processing.
        throw new AccessDeniedHttpException(
            'Cannot reliably determine the institution of the actor, denying processing of command'
        );
    }

    /**
     * @param string      $institution
     * @param string|null $actorId
     */
    private function assertCommandMayBeProcessedOnBehalfOf($institution, $actorId)
    {
        $whitelistService = $this->get('surfnet_stepup_middleware_api.service.whitelist_entry');

        if ($whitelistService->isWhitelisted($institution)) {
            return;
        }

        if (!$actorId) {
            throw new AccessDeniedHttpException(sprintf(
                'Institution "%s" is not on the whitelist and no actor is found, processing of command denied',
                $institution
            ));
        }

        $identityService = $this->get('surfnet_stepup_middleware_api.service.identity');
        $registrationAuthorityCredentials = $identityService->findRegistrationAuthorityCredentialsOf($actorId);

        if ($registrationAuthorityCredentials->isSraa()) {
            return;
        }

        throw new AccessDeniedHttpException(sprintf(
            'Institution "%s" is not on the whitelist and actor "%s" is not an SRAA, processing of command denied',
            $institution,
            $actorId
        ));
    }
}

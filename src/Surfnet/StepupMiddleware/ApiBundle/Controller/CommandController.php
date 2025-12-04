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

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\CommandAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventSourcing\MetadataEnricher;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use function sprintf;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class CommandController extends AbstractController
{
    public function __construct(
        private readonly TransactionAwarePipeline $pipeline,
        private readonly MetadataEnricher $metadataEnricher,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly LoggerInterface $logger,
        private readonly CommandAuthorizationService $commandAuthorizationService,
    ) {
    }

    public function handle(AbstractCommand $command, Metadata $metadata, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS']);
        $this->logger->notice(sprintf('Received request to process Command "%s"', $command));

        $this->metadataEnricher->setMetadata($metadata);

        if ($this->authorizationChecker->isGranted('ROLE_MANAGEMENT')) {
            $this->logger->notice('Command sent through Management API, not enforcing Whitelist');
        } else {
            $this->logger->notice('Ensuring that the actor institution is on the whitelist, or the actor is SRAA');

            $this->handleAuthorization($command, $metadata);
        }

        try {
            $command = $this->pipeline->process($command);
        } catch (ForbiddenException $e) {
            throw new AccessDeniedHttpException(
                sprintf('Processing of command "%s" is forbidden for this client', $command),
                $e,
            );
        }

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response = new JsonResponse(['command' => $command->UUID, 'processed_by' => $serverName]);

        $this->logger->notice(sprintf('Command "%s" has been successfully processed', $command));

        return $response;
    }

    private function resolveInstitution(Command $command, Metadata $metadata): Institution
    {
        if ($metadata->actorInstitution) {
            return new Institution($metadata->actorInstitution);
        }

        // the createIdentityCommand is used to create an Identity for a new user,
        // the updateIdentityCommand is used to update name or email of an identity
        // Both are only sent by the SS when the Identity is not logged in yet,
        // thus there is not Metadata::actorInstitution,
        if ($command instanceof CreateIdentityCommand || $command instanceof UpdateIdentityCommand) {
            return new Institution($command->institution);
        }

        // conservative, if we cannot determine an institution, deny processing.
        throw new AccessDeniedHttpException(
            'Cannot reliably determine the institution of the actor, denying processing of command',
        );
    }

    private function handleAuthorization(Command $command, Metadata $metadata): void
    {
        // Get the actorId and actorInstitution from the metadata
        // Be aware that these values could be null when executing commands where we shouldn't log in for
        // - CreateIdentityCommand
        // - UpdateIdentityCommand
        $actorId = is_null($metadata->actorId) ? null : new IdentityId($metadata->actorId);
        $actorInstitution = is_null($metadata->actorInstitution) ? null : new Institution($metadata->actorInstitution);

        // The institution of an actor should be whitelisted or the actor should be SRAA
        // Be aware that the actor metadata is not always present, see self::resolveInstitution
        $this->logger->notice('Ensuring that the actor institution is on the whitelist, or the actor is SRAA');
        $institution = $this->resolveInstitution($command, $metadata);
        if (!$this->commandAuthorizationService->isInstitutionWhitelisted($institution, $actorId)) {
            throw new AccessDeniedHttpException(
                sprintf(
                    'Institution "%s" is not on the whitelist and actor "%s" is not an SRAA, processing of command denied',
                    $institution,
                    $metadata->actorId,
                ),
            );
        }

        $this->logger->notice(
            'Ensuring that the actor is allowed to execute a command based on the fine grained authorization configuration',
        );

        // Validate that if a command is an SelfServiceExecutable we may execute the command
        // This should be an SRAA or the actor itself
        // Be aware that for the CreateIdentityCommand and UpdateIdentityCommand the actorId is unknown because we aren't logged in yet
        if (!$this->commandAuthorizationService->maySelfserviceCommandBeExecutedOnBehalfOf(
            $command,
            $actorId,
        )) {
            $message = 'Processing of SelfService command denied, see log entries for details';
            if ($command instanceof SelfServiceExecutable) {
                $message = sprintf(
                    'The actor "%s" is not allowed to act on behalf of identity "%s" processing of SelfService command denied',
                    new IdentityId($metadata->actorId),
                    $command->getIdentityId(),
                );
            }
            throw new AccessDeniedHttpException($message);
        }

        // Validate that if a command is an RAExecutable we may execute the command
        // This should be an SRAA or an RAA which is configured to act on behalf of the institution
        if (!$this->commandAuthorizationService->mayRaCommandBeExecutedOnBehalfOf(
            $command,
            $actorId,
            $actorInstitution,
        )) {
            throw new AccessDeniedHttpException(
                sprintf(
                    'The actor "%s" is not allowed to act on behalf of institution  "%s" processing of RA command denied',
                    new IdentityId($metadata->actorId),
                    $institution,
                ),
            );
        }
    }
}

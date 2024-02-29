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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline;

use Psr\Log\LoggerInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\DeprovisionExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\ManagementExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizingStage implements Stage
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function process(Command $command): Command
    {
        $this->logger->debug(sprintf('Processing authorization for command "%s"', $command));

        $allowedRoles = [];
        if ($command instanceof SelfServiceExecutable) {
            $allowedRoles[] = 'ROLE_SS';
        }

        if ($command instanceof RaExecutable) {
            $allowedRoles[] = 'ROLE_RA';
        }

        if ($command instanceof ManagementExecutable) {
            $allowedRoles[] = 'ROLE_MANAGEMENT';
        }

        if ($command instanceof DeprovisionExecutable) {
            $allowedRoles[] = 'ROLE_DEPROVISION';
        }

        if ($allowedRoles === []) {
            $this->logger->debug(sprintf('No authorization required for command "%s"', $command));

            return $command;
        }

        if (!$this->clientHasAtLeastOneRole($allowedRoles)) {
            $this->logger->error(
                sprintf(
                    'Client is not authorized to execute command "%s", it does not have (one of) the required role(s) "%s"',
                    $command,
                    implode(', ', $allowedRoles),
                ),
            );

            throw new ForbiddenException(sprintf('Processing of Command "%s" is forbidden.', $command));
        }

        $this->logger->debug(sprintf('Client authorized to execute command "%s"', $command));

        return $command;
    }

    /**
     * @return bool
     */
    private function clientHasAtLeastOneRole(array $rolesToCheck): bool
    {
        foreach ($rolesToCheck as $role) {
            if ($this->authorizationChecker->isGranted([$role])) {
                return true;
            }
        }

        return false;
    }
}

<?php

/**
 * Copyright 2021 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final readonly class TransactionHelper
{
    public function __construct(
        private Pipeline $pipeline,
        private BufferedEventBus $eventBus,
        private DBALConnectionHelper $connection,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * Console commands run without an authenticated security token. Commands processed through the
     * pipeline (including ones triggered internally by event processors, e.g. institution
     * configuration bootstrapping) are checked by the AuthorizingStage, so bootstrap console commands
     * that need to authorize such internally-triggered commands must call this explicitly. Limited to
     * ROLE_SS, ROLE_RA and ROLE_MANAGEMENT: the identity/vetting/configuration commands the bootstrap
     * console commands dispatch through this pipeline. ROLE_DEPROVISION is deliberately excluded,
     * nothing in these flows needs it. Guarded to CLI only, so this can never grant privileges to an
     * HTTP request even if called from a context that shouldn't.
     */
    public function authorizeConsoleContext(): void
    {
        if (PHP_SAPI === 'cli' && $this->tokenStorage->getToken() === null) {
            $roles = ['ROLE_SS', 'ROLE_RA', 'ROLE_MANAGEMENT'];
            $this->tokenStorage->setToken(
                new UsernamePasswordToken(new InMemoryUser('console', null, $roles), 'api', $roles),
            );
        }
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function finishTransaction(): void
    {
        $this->eventBus->flush();
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function process(AbstractCommand $command): AbstractCommand
    {
        return $this->pipeline->process($command);
    }
}

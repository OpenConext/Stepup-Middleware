<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * RecoveryTokenProjector only started removing recovery token rows on IdentityForgottenEvent after
 * these rows were already written for identities forgotten earlier. This command removes the stale rows
 * of identities that are currently forgotten.
 *
 * Replaying IdentityForgottenEvent for RecoveryTokenProjector is not an option: the projector removes every
 * row of the identity, which would also wipe tokens registered after the identity was restored. This
 * command only touches identities whose read model is still anonymised, so it is idempotent and safe to
 * run at any time.
 */
#[AsCommand(
    name: 'stepup:recovery-token:remove-forgotten',
    description: 'Removes the recovery tokens of identities that have been forgotten. Idempotent.'
)]
final class RemoveForgottenIdentitiesRecoveryTokensCommand
{
    public function __construct(private readonly RecoveryTokenRepository $recoveryTokenRepository)
    {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Report how many recovery tokens would be removed without removing them', name: 'dry-run')]
        bool $dryRun = false,
    ): int {
        if ($dryRun) {
            $output->writeln(sprintf(
                '<info>%d recovery token(s) of forgotten identities would be removed.</info>',
                $this->recoveryTokenRepository->countForForgottenIdentities(),
            ));

            return 0;
        }

        $output->writeln(sprintf(
            '<info>Removed %d recovery token(s) of forgotten identities.</info>',
            $this->recoveryTokenRepository->removeForForgottenIdentities(),
        ));

        return 0;
    }
}

<?php

/**
 * Copyright 2021 SURFnet bv
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

use Exception;
use InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\BootstrapCommandService;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TransactionHelper;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'middleware:migrate:vetted-tokens',
    description: 'Migrates the tokens of an identity to a new institution while preserving the old tokens'
)]
final class MigrateSecondFactorCommand
{
    public function __construct(private readonly BootstrapCommandService $bootstrapService, private readonly TransactionHelper $transactionHelper)
    {
    }

    public function __invoke(
        #[Argument(description: 'The old NameID of the identity used as the source of the tokens to move', name: 'old-name-id')]
        string $oldNameId,
        #[Argument(description: 'The new NameID of the identity to move the tokens to', name: 'new-name-id')]
        string $newNameId,
        #[Argument(description: 'The institution of the target identity', name: 'target-institution')]
        ?string $targetInstitution,
        #[Argument(description: 'The e-mail address of the identity to create', name: 'email')]
        ?string $email,
        OutputInterface $output
    ): int {
        $sourceNameId = new NameId($oldNameId);
        $targetNameId = new NameId($newNameId);

        $output->writeln(sprintf('<comment>Starting token migration for %s</comment>', $sourceNameId));
        $sourceIdentity = $this->bootstrapService->getIdentityByNameId($sourceNameId);

        if ($sourceIdentity === null) {
            throw new InvalidArgumentException("oldNameId could net be resolved to a Identity.");
        }

        $targetIdentity = $this->bootstrapService->getIdentityByNameId($targetNameId);

        try {
            $this->transactionHelper->beginTransaction();

            // Check if target identity should be created
            if (!$targetIdentity instanceof Identity) {
                $output->writeln(
                    sprintf('<info>Target with NameID %s does not exist, creating new identity</info>', $targetNameId),
                );

                $identityId = $this->createIdentity($targetNameId, $sourceIdentity, $targetInstitution, $email);

                $output->writeln(
                    sprintf('<info>Successfully created identity with UUID %s</info>', $identityId),
                );


                $targetIdentity = $this->bootstrapService->getIdentityByNameId($targetNameId);
            }

            // Foreach token, perform the token move command
            $sourceVettedSecondFactors = $this->bootstrapService->getVettedSecondFactorsFromIdentity($sourceIdentity);
            $targetVettedSecondFactors = $this->bootstrapService->getVettedSecondFactorsFromIdentity($targetIdentity);
            foreach ($sourceVettedSecondFactors as $secondFactor) {
                if (!$this->tokenExists($targetVettedSecondFactors, $secondFactor)) {
                    $this->bootstrapService->migrateVettedSecondFactor($sourceIdentity, $targetIdentity, $secondFactor);
                    $output->writeln(sprintf('<comment>Moved token %s</comment>', $secondFactor->id));
                } else {
                    $output->writeln(
                        sprintf('<info>Skipped moving token %s, already present"</info>', $secondFactor->id),
                    );
                }
            }

            $this->transactionHelper->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to move the tokens of identity: "%s"</error>',
                    $e->getMessage(),
                ),
            );
            $this->transactionHelper->rollback();
            return 1;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully moved tokens from identity %s to identity %s</info>',
                $sourceIdentity->id,
                $targetIdentity->id,
            ),
        );
        return 0;
    }

    /**
     * @return string
     */
    private function createIdentity(NameId $targetNameId, Identity $sourceIdentity, ?string $newInstitution, ?string $newEmail): string
    {
        if (!$newInstitution || !$newEmail) {
            throw new InvalidArgumentException("Missing email and institution");
        }

        $institution = new Institution($newInstitution);

        $identity = $this->bootstrapService->createIdentity(
            $institution,
            $targetNameId,
            $sourceIdentity->commonName->getCommonName(),
            $newEmail,
            $sourceIdentity->preferredLocale->getLocale(),
        );

        return $identity->id;
    }

    private function tokenExists(array $targetSecondFactors, VettedSecondFactor $sourceSecondFactor): bool
    {
        foreach ($targetSecondFactors as $secondFactor) {
            if ($secondFactor->isEqual($sourceSecondFactor)) {
                return true;
            }
        }
        return false;
    }
}

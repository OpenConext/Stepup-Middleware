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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use function sprintf;

final class MigrateSecondFactorCommand extends Command
{
    public function __construct(
        private readonly BootstrapCommandService $bootstrapService,
        private readonly TransactionHelper $transactionHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrates the tokens of an identity to a new institution while preserving the old tokens')
            ->addArgument(
                'old-name-id',
                InputArgument::REQUIRED,
                'The old NameID of the identity used as the source of the tokens to move',
            )
            ->addArgument(
                'new-name-id',
                InputArgument::REQUIRED,
                'The new NameID of the identity to move the tokens to',
            )
            ->addArgument('target-institution', InputArgument::OPTIONAL, 'The institution of the target identity')
            ->addArgument('email', InputArgument::OPTIONAL, 'The e-mail address of the identity to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $sourceNameId = new NameId($input->getArgument('old-name-id'));
        $targetNameId = new NameId($input->getArgument('new-name-id'));

        $this->bootstrapService->setToken(
            new AnonymousToken('cli.bootstrap-yubikey-token', 'cli', ['ROLE_SS', 'ROLE_RA']),
        );

        $output->writeln(sprintf('<comment>Starting token migration for %s</comment>', $sourceNameId));
        $sourceIdentity = $this->bootstrapService->getIdentityByNameId($sourceNameId);
        $targetIdentity = $this->bootstrapService->getIdentityByNameId($targetNameId);

        try {
            $this->transactionHelper->beginTransaction();

            // Check if target identity should be created
            if (!$targetIdentity instanceof Identity) {
                $output->writeln(
                    sprintf('<info>Target with NameID %s does not exist, creating new identity</info>', $targetNameId),
                );

                $identityId = $this->createIdentity($targetNameId, $sourceIdentity, $input);

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
            throw $e;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully moved tokens from identity %s to identity %s</info>',
                $sourceIdentity->id,
                $targetIdentity->id,
            ),
        );
    }

    /**
     * @return string
     */
    private function createIdentity(NameId $targetNameId, Identity $sourceIdentity, InputInterface $input)
    {
        $newInstitution = $input->getArgument('target-institution');
        $newEmail = $input->getArgument('email');
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
            if ($secondFactor->isEqual($secondFactor)) {
                return true;
            }
        }
        return false;
    }
}

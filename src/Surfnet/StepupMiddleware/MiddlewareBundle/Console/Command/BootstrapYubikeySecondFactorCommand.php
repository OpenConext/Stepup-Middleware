<?php

/**
 * Copyright 2020 SURFnet bv
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
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\BootstrapCommandService;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TransactionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BootstrapYubikeySecondFactorCommand extends Command
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
            ->setDescription('Creates a Yubikey second factor for a specified user')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument(
                'yubikey',
                InputArgument::REQUIRED,
                'The public ID of the Yubikey. Remove the last 32 characters of a Yubikey OTP to acquire this.',
            )
            ->addArgument(
                'registration-status',
                InputArgument::REQUIRED,
                'Valid arguments: unverified, verified, vetted',
            )
            ->addArgument('actor-id', InputArgument::REQUIRED, 'The id of the vetting actor');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $registrationStatus = $input->getArgument('registration-status');
        $this->bootstrapService->validRegistrationStatus($registrationStatus);

        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->bootstrapService->requiresMailVerification($institutionText);
        $registrationStatus = $input->getArgument('registration-status');
        $yubikey = $input->getArgument('yubikey');
        $actorId = $input->getArgument('actor-id');
        $this->bootstrapService->enrichEventMetadata($actorId);
        if (!$this->bootstrapService->identityExists($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" does not exist, create it first.</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution(),
                ),
            );

            return;
        }
        $identity = $this->bootstrapService->getIdentity($nameId, $institution);
        $output->writeln(
            sprintf('<comment>Adding a %s Yubikey token for %s</comment>', $registrationStatus, $identity->commonName),
        );
        $this->transactionHelper->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->bootstrapService->proveYubikeyPossession($secondFactorId, $identity, $yubikey);
                    break;
                case "verified":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->bootstrapService->proveYubikeyPossession($secondFactorId, $identity, $yubikey);
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified Yubikey token</comment>');
                        $this->bootstrapService->verifyEmail($identity, 'yubikey');
                    }
                    break;
                case "vetted":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->bootstrapService->proveYubikeyPossession($secondFactorId, $identity, $yubikey);
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified Yubikey token</comment>');
                        $this->bootstrapService->verifyEmail($identity, 'yubikey');
                    }
                    $output->writeln('<comment>Vetting the verified Yubikey token</comment>');
                    $this->bootstrapService->vetSecondFactor(
                        'yubikey',
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $yubikey,
                    );
                    break;
            }
            $this->transactionHelper->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the Yubikey token: "%s"</error>',
                    $e->getMessage(),
                ),
            );
            $this->transactionHelper->rollback();
            throw $e;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully registered a second factor with UUID %s</info>',
                $secondFactorId,
            ),
        );
    }
}

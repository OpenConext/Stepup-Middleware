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
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'middleware:bootstrap:sms',
    description: 'Creates a SMS second factor for a specified user'
)]
final class BootstrapSmsSecondFactorCommand
{
    public function __construct(private readonly BootstrapCommandService $bootstrapService, private readonly TransactionHelper $transactionHelper)
    {
    }

    public function __invoke(#[Argument(description: 'The NameID of the identity to create', name: 'name-id')]
    string $nameId, #[Argument(description: 'The institution of the identity to create', name: 'institution')]
    string $institution, #[Argument(description: 'The phone number of the user should be formatted like "+31 (0) 612345678"', name: 'phone-number')]
    string $phoneNumber, #[Argument(description: 'Valid arguments: unverified, verified, vetted', name: 'registration-status')]
    string $registrationStatus, #[Argument(description: 'The id of the vetting actor', name: 'actor-id')]
    string $actorId, OutputInterface $output): int
    {
        $this->bootstrapService->validRegistrationStatus($registrationStatus);

        $nameId = new NameId($nameId);
        $institutionText = $institution;
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->bootstrapService->requiresMailVerification($institutionText);

        $this->bootstrapService->enrichEventMetadata($actorId);
        if (!$this->bootstrapService->identityExists($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" does not exist, create it first.</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution(),
                ),
            );

            return 1;
        }
        $identity = $this->bootstrapService->getIdentity($nameId, $institution);
        $output->writeln(
            sprintf('<comment>Adding a %s SMS token for %s</comment>', $registrationStatus, $identity->commonName),
        );
        $this->transactionHelper->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->bootstrapService->provePhonePossession($secondFactorId, $identity, $phoneNumber);
                    break;
                case "verified":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->bootstrapService->provePhonePossession($secondFactorId, $identity, $phoneNumber);
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified SMS token</comment>');
                        $this->bootstrapService->verifyEmail($identity, 'sms');
                    }
                    break;
                case "vetted":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->bootstrapService->provePhonePossession($secondFactorId, $identity, $phoneNumber);
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified SMS token</comment>');
                        $this->bootstrapService->verifyEmail($identity, 'sms');
                    }
                    $output->writeln('<comment>Vetting the verified SMS token</comment>');
                    $this->bootstrapService->vetSecondFactor(
                        'sms',
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $phoneNumber,
                    );
                    break;
            }
            $this->transactionHelper->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the SMS token: "%s"</error>',
                    $e->getMessage(),
                ),
            );
            $this->transactionHelper->rollback();
            return 1;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully registered a SMS token with UUID %s</info>',
                $secondFactorId,
            ),
        );
        return 0;
    }
}

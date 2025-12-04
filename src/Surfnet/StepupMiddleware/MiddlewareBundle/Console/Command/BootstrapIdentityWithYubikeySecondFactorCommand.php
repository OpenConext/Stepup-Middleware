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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Exception;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand
    as BootstrapIdentityWithYubikeySecondFactorIdentityCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TransactionHelper;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[AsCommand(
    name: 'middleware:bootstrap:identity-with-yubikey',
    description: 'Creates an identity with a vetted Yubikey second factor',
)]
final class BootstrapIdentityWithYubikeySecondFactorCommand
{
    public function __construct(private readonly IdentityRepository $projectionRepository, private readonly TransactionHelper $transactionHelper)
    {
    }

    public function __invoke(
        #[Argument(description: 'The NameID of the identity to create', name: 'name-id')]
        string $nameId,
        #[Argument(description: 'The institution of the identity to create', name: 'institution')]
        string $institution,
        #[Argument(description: 'The Common Name of the identity to create', name: 'common-name')]
        string $commonName,
        #[Argument(description: 'The e-mail address of the identity to create', name: 'email')]
        string $email,
        #[Argument(description: 'The preferred locale of the identity to create', name: 'preferred-locale')]
        string $preferredLocale,
        #[Argument(description: 'The public ID of the Yubikey. Remove the last 32 characters of a Yubikey OTP to acquire this.', name: 'yubikey')]
        string $yubikey,
        OutputInterface $output
    ): int {
        $nameId = new NameId($nameId);
        $institution = new Institution($institution);

        if ($this->projectionRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" already exists</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution(),
                ),
            );

            return 1;
        }

        $command = new BootstrapIdentityWithYubikeySecondFactorIdentityCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->identityId = (string)Uuid::uuid4();
        $command->nameId = $nameId;
        $command->institution = $institution;
        $command->commonName = $commonName;
        $command->email = $email;
        $command->preferredLocale = $preferredLocale;
        $secondFactorId = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->yubikeyPublicId = $yubikey;

        $this->transactionHelper->beginTransaction();

        try {
            $command = $this->transactionHelper->process($command);
            $this->transactionHelper->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the token for identity: "%s"</error>',
                    $e->getMessage(),
                ),
            );

            $this->transactionHelper->rollBack();

            throw $e;
        }

        $output->writeln(
            sprintf(
                '<info>Successfully registered a Yubikey token with UUID %s</info>',
                $secondFactorId,
            ),
        );
        return 0;
    }
}

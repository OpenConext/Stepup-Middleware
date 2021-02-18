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
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapSmsSecondFactorCommand extends AbstractBootstrapCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Creates a SMS second factor for a specified user')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument(
                'phone-number',
                InputArgument::REQUIRED,
                'The phone number of the user should be formatted like "+31 (0) 612345678"'
            )
            ->addArgument(
                'registration-status',
                InputArgument::REQUIRED,
                'Valid arguments: unverified, verified, vetted'
            )
            ->addArgument('actor-id', InputArgument::REQUIRED, 'The id of the vetting actor');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tokenStorage->setToken(
            new AnonymousToken('cli.bootstrap-sms-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );
        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->requiresMailVerification($institutionText);
        $registrationStatus = $input->getArgument('registration-status');
        $phoneNumber = $input->getArgument('phone-number');
        $actorId = $input->getArgument('actor-id');
        $this->enrichEventMetadata($actorId);
        if (!$this->tokenBootstrapService->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" does not exist, create it first.</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );

            return;
        }
        $identity = $this->tokenBootstrapService->findOneByNameIdAndInstitution($nameId, $institution);
        $output->writeln(sprintf('<comment>Adding a %s SMS token for %s</comment>', $registrationStatus, $identity->commonName));
        $this->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    break;
                case "verified":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'sms');
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified SMS token</comment>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    break;
                case "vetted":
                    $output->writeln('<comment>Creating an unverified SMS token</comment>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'sms');
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified SMS token</comment>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    $verifiedSecondFactor = $this->tokenBootstrapService->findVerifiedToken($identity->id, 'sms');
                    $output->writeln('<comment>Vetting the verified SMS token</comment>');
                    $this->vetSecondFactor(
                        'sms',
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $verifiedSecondFactor,
                        $phoneNumber
                    );
                    break;
            }
            $this->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the SMS token: "%s"</error>',
                    $e->getMessage()
                )
            );
            $this->rollback();
            throw $e;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully registered a SMS token with UUID %s</info>',
                $identity->id,
                $registrationStatus,
                $secondFactorId
            )
        );
    }

    private function provePossession($secondFactorId, $identity, $phoneNumber)
    {
        $command = new ProvePhonePossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->phoneNumber = $phoneNumber;
        $this->process($command);
    }
}

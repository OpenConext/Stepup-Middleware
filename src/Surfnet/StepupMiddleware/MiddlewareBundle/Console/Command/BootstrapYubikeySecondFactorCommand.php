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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapYubikeySecondFactorCommand extends AbstractBootstrapCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Creates a Yubikey second factor for a specified user')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument(
                'yubikey',
                InputArgument::REQUIRED,
                'The public ID of the Yubikey. Remove the last 32 characters of a Yubikey OTP to acquire this.'
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
            new AnonymousToken('cli.bootstrap-yubikey-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );
        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->requiresMailVerification($institutionText);
        $registrationStatus = $input->getArgument('registration-status');
        $yubikey = $input->getArgument('yubikey');
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
        $output->writeln(sprintf('<comment>Adding a %s Yubikey token for %s</comment>', $registrationStatus, $identity->commonName));
        $this->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->provePossession($secondFactorId, $identity, $yubikey);
                    break;
                case "verified":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->provePossession($secondFactorId, $identity, $yubikey);
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'yubikey');
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified Yubikey token</comment>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    break;
                case "vetted":
                    $output->writeln('<comment>Creating an unverified Yubikey token</comment>');
                    $this->provePossession($secondFactorId, $identity, $yubikey);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'yubikey');
                    if ($mailVerificationRequired) {
                        $output->writeln('<comment>Creating a verified Yubikey token</comment>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    $verifiedSecondFactor = $this->tokenBootstrapService->findVerifiedToken($identity->id, 'yubikey');
                    $output->writeln('<comment>Vetting the verified Yubikey token</comment>');
                    $this->vetSecondFactor(
                        'yubikey',
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $verifiedSecondFactor,
                        $yubikey
                    );
                    break;
            }
            $this->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the identity: "%s"</error>',
                    $e->getMessage()
                )
            );
            $this->rollback();
            throw $e;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully created identity with UUID %s and %s second factor with UUID %s</info>',
                $identity->id,
                $registrationStatus,
                $secondFactorId
            )
        );
    }

    private function provePossession($secondFactorId, $identity, $phoneNumber)
    {
        $command = new ProveYubikeyPossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->yubikeyPublicId = $phoneNumber;
        $this->process($command);
    }
}

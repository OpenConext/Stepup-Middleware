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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
final class BootstrapIdentityWithSmsSecondFactorCommand extends AbstractBootstrapCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Creates an identity with a SMS second factor')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument('common-name', InputArgument::REQUIRED, 'The Common Name of the identity to create')
            ->addArgument('email', InputArgument::REQUIRED, 'The e-mail address of the identity to create')
            ->addArgument('preferred-locale', InputArgument::REQUIRED, 'The preferred locale of the identity to create')
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
            new AnonymousToken('cli.bootstrap-identity-with-sms-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );
        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->requiresMailVerification($institutionText);
        $commonName = $input->getArgument('common-name');
        $email = $input->getArgument('email');
        $preferredLocale = $input->getArgument('preferred-locale');
        $registrationStatus = $input->getArgument('registration-status');
        $phoneNumber = $input->getArgument('phone-number');
        $actorId = $input->getArgument('actor-id');
        $this->enrichEventMetadata($actorId);
        $identity = false;
        $output->writeln(sprintf('<notice>Adding a %s SMS token for %s</notice>', $registrationStatus, $commonName));
        if ($this->tokenBootstrapService->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<notice>An identity with name ID "%s" from institution "%s" already exists, using that identity</notice>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );
            $identity = $this->tokenBootstrapService->findOneByNameIdAndInstitution($nameId, $institution);
        }
        $this->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();
        if (!$identity) {
            $output->writeln('<notice>Creating a new identity</notice>');
            $identity = $this->createIdentity($institution, $nameId, $commonName, $email, $preferredLocale);
        }
        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    break;
                case "verified":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'sms');
                    if ($mailVerificationRequired) {
                        $output->writeln('<notice>Creating a verified SMS token</notice>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    break;
                case "vetted":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($secondFactorId, $identity, $phoneNumber);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, 'sms');
                    if ($mailVerificationRequired) {
                        $output->writeln('<notice>Creating a verified SMS token</notice>');
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    $verifiedSecondFactor = $this->tokenBootstrapService->findVerifiedToken($identity->id, 'sms');
                    $output->writeln('<notice>Vetting the verified SMS token</notice>');
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
        $command = new ProvePhonePossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->phoneNumber = $phoneNumber;
        $this->process($command);
    }

    private function verifyEmail($identity, $unverifiedSecondFactor)
    {
        $command = new VerifyEmailCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->identityId = $identity->id;
        $command->verificationNonce = $unverifiedSecondFactor->verificationNonce;
        $this->process($command);
    }

    protected function createIdentity(
        Institution $institution,
        NameId $nameId,
        $commonName,
        $email,
        $preferredLocale
    ) {
        $identity = new CreateIdentityCommand();
        $identity->UUID = (string)Uuid::uuid4();
        $identity->id = (string)Uuid::uuid4();
        $identity->institution = $institution->getInstitution();
        $identity->nameId = $nameId->getNameId();
        $identity->commonName = $commonName;
        $identity->email = $email;
        $identity->preferredLocale = $preferredLocale;
        $this->process($identity);

        return $identity;
    }
}

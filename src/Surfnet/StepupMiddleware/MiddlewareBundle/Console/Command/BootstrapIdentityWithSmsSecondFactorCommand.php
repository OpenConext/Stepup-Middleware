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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapIdentityWithSmsSecondFactorCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('middleware:bootstrap:identity-with-sms')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Container $container */
        $container = $this->getApplication()->getKernel()->getContainer();
        $tokenStorage = $container->get('security.token_storage');
        $identityRepository = $container->get('surfnet_stepup_middleware_api.repository.identity');
        $unverifiedSecondFactorRepository = $container->get(
            'surfnet_stepup_middleware_api.repository.unverified_second_factor'
        );
        $verifiedSecondFactorRepository = $container->get(
            'surfnet_stepup_middleware_api.repository.verified_second_factor'
        );
        $pipeline = $container->get('surfnet_stepup_middleware_command_handling.pipeline.transaction_aware_pipeline');
        $eventBus = $container->get('surfnet_stepup_middleware_command_handling.event_bus.buffered');
        $connection = $container->get('surfnet_stepup_middleware_middleware.dbal_connection_helper');

        $tokenStorage->setToken(
            new AnonymousToken('cli.bootstrap-identity-with-sms-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );

        $nameId = new NameId($input->getArgument('name-id'));
        $institution = new Institution($input->getArgument('institution'));
        $commonName = $input->getArgument('common-name');
        $email = $input->getArgument('email');
        $preferredLocale = $input->getArgument('preferred-locale');
        $registrationStatus = $input->getArgument('registration-status');
        $phoneNumber = $input->getArgument('phone-number');
        $identity = false;

        $output->writeln(
            sprintf(
                '<notice>Adding a %s SMS token for %s</notice>',
                $registrationStatus,
                $commonName
            )
        );

        if ($identityRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<notice>An identity with name ID "%s" from institution "%s" already exists, using that identity</notice>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );
            $identity = $identityRepository->findOneByNameIdAndInstitution($nameId, $institution);
        }

        $connection->beginTransaction();

        $secondFactorId = Uuid::uuid4()->toString();

        if (!$identity) {
            $output->writeln('<notice>Creating a new identity</notice>');
            $identity = new CreateIdentityCommand();
            $identity->UUID = (string) Uuid::uuid4();
            $identity->id = (string) Uuid::uuid4();
            $identity->institution = $institution->getInstitution();
            $identity->nameId = $nameId->getNameId();
            $identity->commonName = $commonName;
            $identity->email = $email;
            $identity->preferredLocale = $preferredLocale;
            $pipeline->process($identity);
        }

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($pipeline, $secondFactorId, $identity, $phoneNumber);
                    break;
                case "verified":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($pipeline, $secondFactorId, $identity, $phoneNumber);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $unverifiedSecondFactorRepository->findOneBy(
                        ['identityId' => $identity->id, 'type' => 'sms']
                    );
                    $output->writeln('<notice>Creating a verified SMS token</notice>');
                    $this->verifyEmail($pipeline, $identity, $unverifiedSecondFactor);
                    break;
                case "vetted":
                    $output->writeln('<notice>Creating an unverified SMS token</notice>');
                    $this->provePossession($pipeline, $secondFactorId, $identity, $phoneNumber);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $unverifiedSecondFactorRepository->findOneBy(
                        ['identityId' => $identity->id, 'type' => 'sms']
                    );
                    $output->writeln('<notice>Creating a verified SMS token</notice>');
                    $this->verifyEmail($pipeline, $identity, $unverifiedSecondFactor);
                    /** @var VerifiedSecondFactor $verifiedSecondFactor */
                    $verifiedSecondFactor = $verifiedSecondFactorRepository->findOneBy(
                        ['identityId' => $identity->id, 'type' => 'sms']
                    );
                    $output->writeln('<notice>Vetting the verified SMS token</notice>');
                    $this->vetSecondFactor($pipeline, $identity, $secondFactorId, $verifiedSecondFactor, $phoneNumber);
                    break;
            }

            $eventBus->flush();
            $connection->commit();

        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the identity: "%s"</error>',
                    $e->getMessage()
                )
            );

            $connection->rollBack();

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

    private function provePossession($pipeline, $secondFactorId, $identity, $phoneNumber)
    {
        $command = new ProvePhonePossessionCommand();
        $command->UUID = (string) Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->phoneNumber = $phoneNumber;
        $pipeline->process($command);
    }

    private function verifyEmail($pipeline, $identity, $unverifiedSecondFactor)
    {
        $command = new VerifyEmailCommand();
        $command->UUID = (string) Uuid::uuid4();
        $command->identityId = $identity->id;
        $command->verificationNonce = $unverifiedSecondFactor->verificationNonce;
        $pipeline->process($command);
    }

    private function vetSecondFactor($pipeline, $identity, $secondFactorId, $verifiedSecondFactor, $phoneNumber)
    {
        $command = new VetSecondFactorCommand();
        $command->UUID = (string) Uuid::uuid4();
        $command->authorityId = 'db9b8bdf-720c-44ba-a4c4-154953e45f14';
        $command->identityId = $identity->id;
        $command->secondFactorId = $secondFactorId;
        $command->registrationCode = $verifiedSecondFactor->registrationCode;
        $command->secondFactorType = 'sms';
        $command->secondFactorIdentifier = $phoneNumber;
        $command->documentNumber = '123987';
        $command->identityVerified = true;
        $pipeline->process($command);
    }
}

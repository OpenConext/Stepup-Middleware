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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapGsspSecondFactorCommand extends AbstractBootstrapCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Creates a Generic SAML Second Factor (GSSF) second factor for a specified user')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument(
                'gssp-token-type',
                InputArgument::REQUIRED,
                'The GSSP token type as defined in the GSSP config, for example tiqr or webauthn'
            )
            ->addArgument(
                'gssp-token-identifier',
                InputArgument::REQUIRED,
                'The identifier of the token as registered at the GSSP'
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
            new AnonymousToken('cli.bootstrap-gssp-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );
        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->requiresMailVerification($institutionText);
        $registrationStatus = $input->getArgument('registration-status');
        $tokenType = $input->getArgument('gssp-token-type');
        $tokenIdentifier = $input->getArgument('gssp-token-identifier');
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
        $output->writeln(sprintf('<comment>Adding a %s %s GSSP token for %s</comment>', $registrationStatus, $tokenType, $identity->commonName));
        $this->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->provePossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    break;
                case "verified":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->provePossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, $tokenType);
                    if ($mailVerificationRequired) {
                        $output->writeln(sprintf('<comment>Creating an verified %s token</comment>', $tokenType));
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    break;
                case "vetted":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->provePossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    /** @var UnverifiedSecondFactor $unverifiedSecondFactor */
                    $unverifiedSecondFactor = $this->tokenBootstrapService->findUnverifiedToken($identity->id, $tokenType);
                    if ($mailVerificationRequired) {
                        $output->writeln(sprintf('<comment>Creating an verified %s token</comment>', $tokenType));
                        $this->verifyEmail($identity, $unverifiedSecondFactor);
                    }
                    $verifiedSecondFactor = $this->tokenBootstrapService->findVerifiedToken($identity->id, $tokenType);
                    $output->writeln(sprintf('<comment>Vetting the verified %s token</comment>', $tokenType));
                    $this->vetSecondFactor(
                        $tokenType,
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $verifiedSecondFactor,
                        $tokenIdentifier
                    );
                    break;
            }
            $this->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the %s token: "%s"</error>',
                    $tokenType,
                    $e->getMessage()
                )
            );
            $this->rollback();
            throw $e;
        }
        $output->writeln(
            sprintf(
                '<info>Successfully %s %s second factor with UUID %s</info>',
                $registrationStatus,
                $tokenType,
                $secondFactorId
            )
        );
    }

    private function provePossession($secondFactorId, $identity, $tokenType, $tokenIdentifier)
    {
        $command = new ProveGssfPossessionCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->secondFactorId = $secondFactorId;
        $command->identityId = $identity->id;
        $command->stepupProvider = $tokenType;
        $command->gssfId = $tokenIdentifier;
        $this->process($command);
    }
}

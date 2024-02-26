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
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapGsspSecondFactorCommand extends Command
{
    /**
     * @var BootstrapCommandService
     */
    private $bootstrapService;
    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    public function __construct(BootstrapCommandService $bootstrapService, TransactionHelper $transactionHelper)
    {
        $this->bootstrapService = $bootstrapService;
        $this->transactionHelper = $transactionHelper;
        parent::__construct();
    }

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
        $registrationStatus = $input->getArgument('registration-status');
        $this->bootstrapService->validRegistrationStatus($registrationStatus);

        $this->bootstrapService->setToken(
            new AnonymousToken('cli.bootstrap-gssp-token', 'cli', ['ROLE_SS', 'ROLE_RA'])
        );
        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $mailVerificationRequired = $this->bootstrapService->requiresMailVerification($institutionText);
        $tokenType = $input->getArgument('gssp-token-type');
        $tokenIdentifier = $input->getArgument('gssp-token-identifier');
        $actorId = $input->getArgument('actor-id');
        $this->bootstrapService->enrichEventMetadata($actorId);
        if (!$this->bootstrapService->identityExists($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" does not exist, create it first.</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );

            return;
        }
        $identity = $this->bootstrapService->getIdentity($nameId, $institution);
        $output->writeln(sprintf('<comment>Adding a %s %s GSSP token for %s</comment>', $registrationStatus, $tokenType, $identity->commonName));
        $this->transactionHelper->beginTransaction();
        $secondFactorId = Uuid::uuid4()->toString();

        try {
            switch ($registrationStatus) {
                case "unverified":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->bootstrapService->proveGsspPossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    break;
                case "verified":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->bootstrapService->proveGsspPossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    if ($mailVerificationRequired) {
                        $output->writeln(sprintf('<comment>Creating an verified %s token</comment>', $tokenType));
                        $this->bootstrapService->verifyEmail($identity, $tokenType);
                    }
                    break;
                case "vetted":
                    $output->writeln(sprintf('<comment>Creating an unverified %s token</comment>', $tokenType));
                    $this->bootstrapService->proveGsspPossession($secondFactorId, $identity, $tokenType, $tokenIdentifier);
                    if ($mailVerificationRequired) {
                        $output->writeln(sprintf('<comment>Creating an verified %s token</comment>', $tokenType));
                        $this->bootstrapService->verifyEmail($identity, $tokenType);
                    }
                    $output->writeln(sprintf('<comment>Vetting the verified %s token</comment>', $tokenType));
                    $this->bootstrapService->vetSecondFactor(
                        $tokenType,
                        $actorId,
                        $identity,
                        $secondFactorId,
                        $tokenIdentifier
                    );
                    break;
            }
            $this->transactionHelper->finishTransaction();
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>An Error occurred when trying to bootstrap the %s token: "%s"</error>',
                    $tokenType,
                    $e->getMessage()
                )
            );
            $this->transactionHelper->rollback();
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
}

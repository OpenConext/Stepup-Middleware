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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand
    as BootstrapIdentityWithYubikeySecondFactorIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Surfnet\StepupMiddleware\ManagementBundle\Service\DBALConnectionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

final class BootstrapIdentityWithYubikeySecondFactorCommand extends Command
{
    /**
     * @var DBALConnectionHelper
     */
    private $connection;

    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @var TransactionAwarePipeline
     */
    private $pipeline;

    /**
     * @var ServiceEntityRepository
     */
    private $projectionRepository;

    protected function configure()
    {
        $this
            ->setName('middleware:bootstrap:identity-with-yubikey')
            ->setDescription('Creates an identity with a vetted Yubikey second factor')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument('common-name', InputArgument::REQUIRED, 'The Common Name of the identity to create')
            ->addArgument('email', InputArgument::REQUIRED, 'The e-mail address of the identity to create')
            ->addArgument('preferred-locale', InputArgument::REQUIRED, 'The preferred locale of the identity to create')
            ->addArgument(
                'yubikey',
                InputArgument::REQUIRED,
                'The public ID of the Yubikey. Remove the last 32 characters of a Yubikey OTP to acquire this.'
            );
    }

    public function __construct(
        ServiceEntityRepository $projectionRepository,
        TransactionAwarePipeline $pipeline,
        BufferedEventBus $eventBus,
        DBALConnectionHelper $connection
    ) {
        parent::__construct();
        $this->projectionRepository = $projectionRepository;
        $this->pipeline = $pipeline;
        $this->eventBus = $eventBus;
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Container $container */

        $nameId      = new NameId($input->getArgument('name-id'));
        $institution = new Institution($input->getArgument('institution'));

        if ($this->projectionRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" already exists</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );

            return 1;
        }

        $command                  = new BootstrapIdentityWithYubikeySecondFactorIdentityCommand();
        $command->UUID            = (string) Uuid::uuid4();
        $command->identityId      = (string) Uuid::uuid4();
        $command->nameId          = $input->getArgument('name-id');
        $command->institution     = $input->getArgument('institution');
        $command->commonName      = $input->getArgument('common-name');
        $command->email           = $input->getArgument('email');
        $command->preferredLocale = $input->getArgument('preferred-locale');
        $command->secondFactorId  = (string) Uuid::uuid4();
        $command->yubikeyPublicId = $input->getArgument('yubikey');

        $this->connection->beginTransaction();

        try {
            $command = $this->pipeline->process($command);
            $this->eventBus->flush();

            $this->connection->commit();
        } catch (Exception $e) {
            $output->writeln(sprintf(
                '<error>An Error occurred when trying to bootstrap the token for identity: "%s"</error>',
                $e->getMessage()
            ));

            $this->connection->rollBack();

            throw $e;
        }

        $output->writeln(sprintf(
            '<info>Successfully registered a Yubikey token with UUID %s</info>',
            $command->secondFactorId
        ));
    }
}

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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class BootstrapIdentityCommand extends AbstractBootstrapCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Creates an identity')
            ->addArgument('name-id', InputArgument::REQUIRED, 'The NameID of the identity to create')
            ->addArgument('institution', InputArgument::REQUIRED, 'The institution of the identity to create')
            ->addArgument('common-name', InputArgument::REQUIRED, 'The Common Name of the identity to create')
            ->addArgument('email', InputArgument::REQUIRED, 'The e-mail address of the identity to create')
            ->addArgument('preferred-locale', InputArgument::REQUIRED, 'The preferred locale of the identity to create')
            ->addArgument('actor-id', InputArgument::REQUIRED, 'The id of the vetting actor');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tokenStorage->setToken(
            new AnonymousToken('cli.bootstrap-identity-with-sms-token', 'cli', ['ROLE_SS'])
        );

        $nameId = new NameId($input->getArgument('name-id'));
        $institutionText = $input->getArgument('institution');
        $institution = new Institution($institutionText);
        $commonName = $input->getArgument('common-name');
        $email = $input->getArgument('email');
        $preferredLocale = $input->getArgument('preferred-locale');
        $actorId = $input->getArgument('actor-id');

        $this->enrichEventMetadata($actorId);

        $output->writeln(sprintf('<comment>Adding an identity named: %s</comment>', $commonName));
        if ($this->tokenBootstrapService->hasIdentityWithNameIdAndInstitution($nameId, $institution)) {
            $output->writeln(
                sprintf(
                    '<error>An identity with name ID "%s" from institution "%s" already exists</error>',
                    $nameId->getNameId(),
                    $institution->getInstitution()
                )
            );
            return;
        }
        try {
            $this->beginTransaction();
            $output->writeln('<info>Creating a new identity</info>');
            $identity = $this->createIdentity($institution, $nameId, $commonName, $email, $preferredLocale);
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
            sprintf('<info>Successfully created identity with UUID %s</info>', $identity->id)
        );
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

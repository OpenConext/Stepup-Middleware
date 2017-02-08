<?php

/**
 * Copyright 2017 SURFnet B.V.
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
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

final class MigrateInstitutionConfigurationsCommand extends Command
{
    protected function configure()
    {
        $this->setName('stepup:migrate:institution-configuration');
        $this->setDescription(
            'Migrates institution configurations to work with UUIDv5 identifiers'
            . 'based on institutions with normalized casing'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $output->writeln([
            '<error>WARNING: you are about to migrate institution configurations'
            . ' to work with identifiers based on insitutions with normalized casing.</error>',
            '<error>This command is intended to be run only once.</error>',
            ''
        ]);

        $confirmationQuestion = new ConfirmationQuestion(
            'Are you sure you want to run the institution configuration migrations? [y/N]',
            false
        );
        $confirmed = $questionHelper->ask($input, $output, $confirmationQuestion);

        if (!$confirmed) {
            $output->writeln('<info>Exiting without running migrations.</info>');
            return;
        }

        /** @var Container $container */
        $container = $this->getApplication()->getKernel()->getContainer();

        $tokenStorage     = $container->get('security.token_storage');
        $connectionHelper = $container->get('surfnet_stepup_middleware_middleware.dbal_connection_helper');
        $provider         = $container->get('surfnet_stepup_middleware_middleware.institution_configuration_provider');
        $pipeline         = $container->get('pipeline');
        $entityManager    = $container->get('doctrine.orm.middleware_entity_manager');

        // The InstitutionConfiguration commands require ROLE_MANAGEMENT, AddRaLocation requires ROLE_RA
        // Note that the new events will not have any actor metadata associated with them
        $tokenStorage->setToken(
            new AnonymousToken('cli.institution_configuration_migration', 'cli', ['ROLE_MANAGEMENT', 'ROLE_RA'])
        );

        $connectionHelper->beginTransaction();

        try {
            $state = $provider->loadData();

            foreach ($state->inferRemovalCommands() as $removalCommand) {
                $pipeline->process($removalCommand);
            }
            $entityManager->flush();
            $entityManager->clear();

            foreach ($state->inferCreateCommands() as $createCommand) {
                $pipeline->process($createCommand);
            }
            $entityManager->flush();
            $entityManager->clear();

            foreach ($state->inferReconfigureCommands() as $reconfigureCommand) {
                $pipeline->process($reconfigureCommand);
            }
            $entityManager->flush();
            $entityManager->clear();

            foreach ($state->inferAddRaLocationCommands() as $addRaLocationCommand) {
                $pipeline->process($addRaLocationCommand);
            }
            $entityManager->flush();
            $entityManager->clear();

            $connectionHelper->commit();
            $output->writeln('<info>Successfully migrated institution configurations.</info>');
        } catch (Exception $exception) {
            $output->writeln(
                sprintf('<error>Could not migrate institution configurations: %s</error>', $exception->getMessage())
            );
            $connectionHelper->rollBack();

            throw $exception;
        }
    }
}

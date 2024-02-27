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

use Assert\Assertion;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\DBALConnectionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The EmailVerifiedSecondFactorRemindersCommand can be run to send reminders to token registrants.
 *
 * The command utilizes a specific service for this task (VerifiedSecondFactorReminderService). Input validation is
 * performed on the incoming request parameters.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class EmailVerifiedSecondFactorRemindersCommand extends Command
{
    private TransactionAwarePipeline $pipeline;

    private BufferedEventBus $eventBus;

    private DBALConnectionHelper $connection;

    private LoggerInterface $logger;

    protected function configure(): void
    {
        $this
            ->setName('middleware:cron:email-reminder')
            ->setDescription('Sends email reminders to identities with verified tokens more than 7 days old.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run in dry mode, not sending any email'
            )
            ->addOption(
                'date',
                null,
                InputOption::VALUE_OPTIONAL,
                'The date (Y-m-d) that should be used for sending reminder email messages, defaults to TODAY - 7'
            );
    }

    public function __construct(
        TransactionAwarePipeline $pipeline,
        BufferedEventBus $eventBus,
        DBALConnectionHelper $connection,
        LoggerInterface $logger
    ) {
        $this->pipeline = $pipeline;
        $this->eventBus = $eventBus;
        $this->connection = $connection;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->validateInput($input);
        } catch (InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error(sprintf('Invalid arguments passed to the %s', $this->getName()), [$e->getMessage()]);
            return 1;
        }

        $date = new DateTime();
        $date->sub(new DateInterval('P7D'));
        if ($input->hasOption('date') && !is_null($input->getOption('date'))) {
            $date = DateTime::createFromFormat('Y-m-d', $input->getOption('date'));
        }

        $dryRun = false;
        if ($input->hasOption('dry-run') && !is_null($input->getOption('dry-run'))) {
            $dryRun = $input->getOption('dry-run');
        }

        $command = new SendVerifiedSecondFactorRemindersCommand();
        $command->requestedAt = $date;
        $command->dryRun = $dryRun;
        $command->UUID = Uuid::uuid4()->toString();

        $this->connection->beginTransaction();
        try {
            $this->pipeline->process($command);
            $this->eventBus->flush();

            $this->connection->commit();
        } catch (Exception $e) {
            $output->writeln('<error>An Error occurred while sending reminder email messages.</error>');
            $this->connection->rollBack();
            throw $e;
        }
    }

    private function validateInput(InputInterface $input): void
    {
        if ($input->hasOption('date')) {
            $date = $input->getOption('date');
            Assertion::nullOrDate($date, 'Y-m-d', 'Expected date to be a string and formatted in the Y-m-d date format');
        }

        if ($input->hasOption('dry-run')) {
            $dryRun = $input->getOption('dry-run');
            Assertion::nullOrBoolean($dryRun, 'Expected dry-run parameter to be a boolean value.');
        }
    }
}

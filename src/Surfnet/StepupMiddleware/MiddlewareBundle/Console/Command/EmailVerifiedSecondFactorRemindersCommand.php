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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The EmailVerifiedSecondFactorRemindersCommand can be run to send reminders to token registrants.
 *
 * The command utilizes a specific service for this task (VerifiedSecondFactorReminderService). Input validation is
 * performed on the incoming request parameters.
 *
 * @TODO Once on Symfony 7.4, remove the `extends Command` and the parent constructor call
 * @see https://github.com/symfony/symfony/commit/1886c105df2772c0a1a17fa739318c3bfb731ce9
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[AsCommand(
    name: 'middleware:cron:email-reminder',
    description: 'Sends email reminders to identities with verified tokens more than 7 days old.'
)]
final class EmailVerifiedSecondFactorRemindersCommand extends Command
{
    public function __construct(
        private readonly TransactionAwarePipeline $pipeline,
        private readonly BufferedEventBus $eventBus,
        private readonly DBALConnectionHelper $connection,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Run in dry mode, not sending any email', name: 'dry-run')]
        bool $dryRun = false,
        #[Option(description: 'The date (Y-m-d) that should be used for sending reminder email messages, defaults to TODAY - 7', name: 'date')]
        ?string $date = null,
    ): int {
        try {
            Assertion::nullOrDate(
                $date,
                'Y-m-d',
                'Expected date to be a string and formatted in the Y-m-d date format',
            );
        } catch (InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error(sprintf('Invalid arguments passed to the %s', $this->getName()), [$e->getMessage()]);
            return 1;
        }

        $now = new DateTime();
        $now->sub(new DateInterval('P7D'));
        if ($date) {
            $receivedDate = $date;
            $date = DateTime::createFromFormat('Y-m-d', $receivedDate);
            if ($date === false) {
                $output->writeln(
                    sprintf(
                        '<error>Error processing the "date" option. Please review the received input: "%s" </error>',
                        $receivedDate
                    )
                );
                return 1;
            }
        }

        $command = new SendVerifiedSecondFactorRemindersCommand();
        $command->requestedAt = $date ?: $now;
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
        return 0;
    }
}

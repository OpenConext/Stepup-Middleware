<?php

/**
 * Copyright 2025 SURFnet bv
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

declare(strict_types=1);

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Console\Command;

use DateInterval;
use DateTime;
use Exception;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\EmailVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\DBALConnectionHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class EmailVerifiedSecondFactorRemindersCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private TransactionAwarePipeline&MockInterface $pipeline;
    private BufferedEventBus&MockInterface $eventBus;
    private DBALConnectionHelper&MockInterface $connection;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->pipeline = m::mock(TransactionAwarePipeline::class);
        $this->eventBus = m::mock(BufferedEventBus::class);
        $this->connection = m::mock(DBALConnectionHelper::class);
        $logger = new NullLogger();

        $command = new EmailVerifiedSecondFactorRemindersCommand(
            $this->pipeline,
            $this->eventBus,
            $this->connection,
            $logger,
        );

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function it_successfully_executes_with_default_date(): void
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();

        // Capture the command passed to pipeline
        $this->pipeline->shouldReceive('process')
            ->once()
            ->with(m::type(SendVerifiedSecondFactorRemindersCommand::class))
            ->andReturnUsing(function (SendVerifiedSecondFactorRemindersCommand $cmd) {
                // Verify the command has correct properties
                $this->assertInstanceOf(DateTime::class, $cmd->requestedAt);
                $this->assertFalse($cmd->dryRun);
                $this->assertNotEmpty($cmd->UUID);

                // Verify date is 7 days ago
                $expectedDate = new DateTime();
                $expectedDate->sub(new DateInterval('P7D'));
                $this->assertEquals($expectedDate->format('Y-m-d'), $cmd->requestedAt->format('Y-m-d'));

                return $cmd;
            });

        $this->eventBus->shouldReceive('flush')->once();

        // Execute command
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function it_successfully_executes_with_custom_date(): void
    {
        $customDate = '2024-12-15';

        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();

        // Capture the command passed to pipeline
        $this->pipeline->shouldReceive('process')
            ->once()
            ->with(m::type(SendVerifiedSecondFactorRemindersCommand::class))
            ->andReturnUsing(function (SendVerifiedSecondFactorRemindersCommand $cmd) use ($customDate) {
                $this->assertEquals($customDate, $cmd->requestedAt->format('Y-m-d'));
                $this->assertFalse($cmd->dryRun);
                return $cmd;
            });

        $this->eventBus->shouldReceive('flush')->once();

        // Execute command with --date option
        $exitCode = $this->commandTester->execute([
            '--date' => $customDate,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function it_successfully_executes_in_dry_run_mode(): void
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();

        // Capture the command passed to pipeline
        $this->pipeline->shouldReceive('process')
            ->once()
            ->with(m::type(SendVerifiedSecondFactorRemindersCommand::class))
            ->andReturnUsing(function (SendVerifiedSecondFactorRemindersCommand $cmd) {
                $this->assertTrue($cmd->dryRun);
                return $cmd;
            });

        $this->eventBus->shouldReceive('flush')->once();

        // Execute command with --dry-run option
        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function it_successfully_executes_with_both_date_and_dry_run(): void
    {
        $customDate = '2024-11-20';

        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();

        // Capture the command passed to pipeline
        $this->pipeline->shouldReceive('process')
            ->once()
            ->with(m::type(SendVerifiedSecondFactorRemindersCommand::class))
            ->andReturnUsing(function (SendVerifiedSecondFactorRemindersCommand $cmd) use ($customDate) {
                $this->assertEquals($customDate, $cmd->requestedAt->format('Y-m-d'));
                $this->assertTrue($cmd->dryRun);
                return $cmd;
            });

        $this->eventBus->shouldReceive('flush')->once();

        // Execute command with both options
        $exitCode = $this->commandTester->execute([
            '--date' => $customDate,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function it_returns_error_for_invalid_date_format(): void
    {
        $invalidDate = 'not-a-date';

        // No transaction should be started for invalid input
        $this->connection->shouldNotReceive('beginTransaction');
        $this->pipeline->shouldNotReceive('process');
        $this->eventBus->shouldNotReceive('flush');

        // Execute command with invalid date
        $exitCode = $this->commandTester->execute([
            '--date' => $invalidDate,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('date', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_returns_error_for_incorrectly_formatted_date(): void
    {
        $invalidDate = '15-12-2024'; // Wrong format, should be Y-m-d

        // No transaction should be started for invalid input
        $this->connection->shouldNotReceive('beginTransaction');
        $this->pipeline->shouldNotReceive('process');
        $this->eventBus->shouldNotReceive('flush');

        // Execute command with incorrectly formatted date
        $exitCode = $this->commandTester->execute([
            '--date' => $invalidDate,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('date', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_rolls_back_transaction_on_exception(): void
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('rollBack')->once();

        $exception = new Exception('Pipeline processing failed');
        $this->pipeline->shouldReceive('process')
            ->once()
            ->andThrow($exception);

        // Event bus should not be flushed when exception occurs
        $this->eventBus->shouldNotReceive('flush');

        // Execute command and expect exception to be re-thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pipeline processing failed');

        $this->commandTester->execute([]);
    }

    #[Test]
    public function it_rolls_back_transaction_when_event_bus_flush_fails(): void
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('rollBack')->once();

        // Pipeline succeeds
        $this->pipeline->shouldReceive('process')->once();

        // Event bus flush throws an exception
        $exception = new Exception('Event bus flush failed');
        $this->eventBus->shouldReceive('flush')
            ->once()
            ->andThrow($exception);

        // Execute command and expect exception to be re-thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Event bus flush failed');

        $this->commandTester->execute([]);
    }
}

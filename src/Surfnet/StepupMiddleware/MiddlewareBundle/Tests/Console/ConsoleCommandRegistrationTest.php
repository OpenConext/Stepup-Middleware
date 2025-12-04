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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test that verifies all console commands are properly registered with Symfony's service container.
 */
class ConsoleCommandRegistrationTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
    }

    /**
     * Data provider for console command registration tests.
     *
     * @return array<string, array{commandName: string}>
     */
    public static function consoleCommandProvider(): array
    {
        return [
            'ReplayEventsCommand' => [
                'commandName' => 'middleware:event:replay',
            ],
            'ReplaySpecificEventsCommand' => [
                'commandName' => 'stepup:event:replay',
            ],
            'BootstrapIdentityCommand' => [
                'commandName' => 'middleware:bootstrap:identity',
            ],
            'BootstrapIdentityWithYubikeySecondFactorCommand' => [
                'commandName' => 'middleware:bootstrap:identity-with-yubikey',
            ],
            'BootstrapSmsSecondFactorCommand' => [
                'commandName' => 'middleware:bootstrap:sms',
            ],
            'BootstrapYubikeySecondFactorCommand' => [
                'commandName' => 'middleware:bootstrap:yubikey',
            ],
            'BootstrapGsspSecondFactorCommand' => [
                'commandName' => 'middleware:bootstrap:gssp',
            ],
            'MigrateSecondFactorCommand' => [
                'commandName' => 'middleware:migrate:vetted-tokens',
            ],
            'EmailVerifiedSecondFactorRemindersCommand' => [
                'commandName' => 'middleware:cron:email-reminder',
            ],
        ];
    }

    #[Test]
    #[DataProvider('consoleCommandProvider')]
    #[DoesNotPerformAssertions]
    public function console_command_is_registered(string $commandName): void
    {
        $this->application->find($commandName);
    }
}

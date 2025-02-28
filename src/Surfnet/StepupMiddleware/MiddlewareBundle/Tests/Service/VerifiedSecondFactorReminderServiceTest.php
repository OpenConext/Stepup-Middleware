<?php
/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Service;

use DateTime;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\DateTime\DateTime as MiddlewareDateTime;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\VerifiedSecondFactorReminderMailService;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\VerifiedSecondFactorReminderService;
use Symfony\Component\Mailer\Exception\TransportException;

class VerifiedSecondFactorReminderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private VerifiedSecondFactorReminderService $service;

    private VerifiedSecondFactorReminderMailService&MockInterface $mailService;

    private LoggerInterface&MockInterface $logger;

    private VerifiedSecondFactorRepository&MockInterface $verifiedSecondFactorRepository;

    private IdentityRepository&MockInterface $identityRepository;

    public function setUp(): void
    {
        $this->mailService = m::mock(VerifiedSecondFactorReminderMailService::class);
        $this->logger = m::mock(LoggerInterface::class);
        $this->verifiedSecondFactorRepository = m::mock(VerifiedSecondFactorRepository::class);
        $this->identityRepository = m::mock(IdentityRepository::class);

        $this->service = new VerifiedSecondFactorReminderService(
            $this->verifiedSecondFactorRepository,
            $this->identityRepository,
            $this->mailService,
            $this->logger,
        );
    }

    public function test_no_token_reminders_sent(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is disabled');

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->with($date)
            ->andReturn([]);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('0 reminders have been sent');

        $this->service->sendReminders($date, false);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_one_token_reminders_sent(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->times()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is disabled');

        $tokens = $this->buildVerifiedSecondFactors(1, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->once()
            ->with($date)
            ->andReturn($tokens);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->with($date)
            ->andReturn($tokens);

        $identity = $this->buildIdentity($tokens[0]);

        $this->identityRepository
            ->shouldReceive('find')
            ->once()
            ->with($tokens[0]->identityId)
            ->andReturn($identity);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('1 token reminder(s) will be sent');

        $this->mailService
            ->shouldReceive('sendReminder')
            ->once()
            ->andReturn(1);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with(
                'Message successfully sent to "mail@example1.org" with token id "fa125c7c-c9ee-11e7-8001-000000000001" of type "yubikey"',
            );

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('1 reminders have been sent');

        $this->service->sendReminders($date, false);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_one_token_reminders_sent_failing_mailer(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is disabled');

        $tokens = $this->buildVerifiedSecondFactors(1, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->once()
            ->with($date)
            ->andReturn($tokens);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('1 token reminder(s) will be sent');

        $identity = $this->buildIdentity($tokens[0]);

        $this->identityRepository
            ->shouldReceive('find')
            ->once()
            ->with($tokens[0]->identityId)
            ->andReturn($identity);

        $this->mailService
            ->shouldReceive('sendReminder')
            ->once()
            ->andThrow(TransportException::class);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with(
                'Message was not sent to "mail@example1.org" with token id "fa125c7c-c9ee-11e7-8001-000000000001" of type "yubikey"',
            );

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('0 reminders have been sent');

        $this->service->sendReminders($date, false);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_multiple_tokens_reminders_sent(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is disabled');

        $tokens = $this->buildVerifiedSecondFactors(9, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->once()
            ->with($date)
            ->andReturn($tokens);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('9 token reminder(s) will be sent');

        foreach ($tokens as $token) {
            $identity = $this->buildIdentity($token);

            $this->identityRepository
                ->shouldReceive('find')
                ->once()
                ->with($token->identityId)
                ->andReturn($identity);

            $this->mailService
                ->shouldReceive('sendReminder')
                ->once()
                ->andReturn(1);
        }

        $this->logger
            ->shouldReceive('info')
            ->with(
                m::pattern(
                    '/^Message successfully sent to "mail@example\d.org" with token id "fa125c7c-c9ee-11e7-800\d-00000000000\d" of type "yubikey"/',
                ),
            )
            ->times(9);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('9 reminders have been sent');

        $this->service->sendReminders($date, false);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_missing_identity(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is disabled');

        $tokens = $this->buildVerifiedSecondFactors(1, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->once()
            ->with($date)
            ->andReturn($tokens);

        $this->identityRepository
            ->shouldReceive('find')
            ->once()
            ->with($tokens[0]->identityId)
            ->andReturn(null);

        $this->logger
            ->shouldReceive('alert')
            ->once()
            ->with('Identity not found with id "1" for second factor token "fa125c7c-c9ee-11e7-8001-000000000001"');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('0 reminders have been sent');

        $this->service->sendReminders($date, false);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_one_token_reminders_sent_dry_run(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is enabled');

        $tokens = $this->buildVerifiedSecondFactors(1, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->with($date)
            ->andReturn($tokens);

        $identity = $this->buildIdentity($tokens[0]);

        $this->identityRepository
            ->shouldReceive('find')
            ->once()
            ->with($tokens[0]->identityId)
            ->andReturn($identity);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('1 token reminder(s) will be sent');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with(
                'Message successfully sent in dry run mode to "mail@example1.org" with token id "fa125c7c-c9ee-11e7-8001-000000000001" of type "yubikey"',
            );

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('1 reminders would have been sent');

        $this->service->sendReminders($date, true);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_multiple_tokens_reminders_sent_dry_run(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is enabled');

        $tokens = $this->buildVerifiedSecondFactors(9, $date);

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->with($date)
            ->andReturn($tokens);

        foreach ($tokens as $token) {
            $identity = $this->buildIdentity($token);

            $this->identityRepository
                ->shouldReceive('find')
                ->once()
                ->with($token->identityId)
                ->andReturn($identity);
        }

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('9 token reminder(s) will be sent');

        $this->logger
            ->shouldReceive('info')
            ->with(
                m::pattern(
                    '/^Message successfully sent in dry run mode to "mail@example\d.org" with token id "fa125c7c-c9ee-11e7-800\d-00000000000\d" of type "yubikey"/',
                ),
            )
            ->times(9);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('9 reminders would have been sent');

        $this->service->sendReminders($date, true);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    public function test_no_token_reminders_sent_dry_run(): void
    {
        $date = new DateTime('2018-01-01');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Sending reminders for date: 2018-01-01. dry run mode is enabled');

        $this->verifiedSecondFactorRepository
            ->shouldReceive('findByDate')
            ->with($date)
            ->andReturn([]);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('0 reminders would have been sent');

        $this->service->sendReminders($date, true);

        $this->assertInstanceOf(VerifiedSecondFactorReminderService::class, $this->service);
    }

    /**
     * @return VerifiedSecondFactor[]
     */
    private function buildVerifiedSecondFactors(int $numberOfResults, DateTime $requestedAt): array
    {
        $collection = [];
        for ($i = 1; $i <= $numberOfResults; $i++) {
            $token = new VerifiedSecondFactor();
            $token->id = "fa125c7c-c9ee-11e7-800{$i}-00000000000{$i}";
            $token->identityId = (string) $i;
            $token->registrationCode = "CODE_{$i}";
            $token->registrationRequestedAt = new MiddlewareDateTime($requestedAt);
            $token->type = 'yubikey';
            $token->commonName = "John Doe {$i}";
            $collection[] = $token;
        }

        return $collection;
    }

    /**
     * @return Identity
     */
    private function buildIdentity(VerifiedSecondFactor $token): Identity
    {
        $identity = new Identity();
        $identity->id = $token->identityId;
        $identity->commonName = new CommonName("John Doe {$token->identityId}");
        $identity->institution = new Institution("Institution {$token->identityId}");
        $identity->preferredLocale = new Locale('nl_NL');
        $identity->email = new Email("mail@example{$token->identityId}.org");

        return $identity;
    }
}

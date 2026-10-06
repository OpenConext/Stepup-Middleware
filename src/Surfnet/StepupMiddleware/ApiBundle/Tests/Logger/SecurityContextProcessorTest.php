<?php

declare(strict_types=1);

/**
 * Copyright 2026 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Logger;

use DateTimeImmutable;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddleware\ApiBundle\Logger\SecurityContextProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityContextProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_enriches_log_record_with_request_and_user_data(): void
    {
        $requestStack = m::mock(RequestStack::class);
        $security = m::mock(Security::class);

        $request = Request::create(
            uri: '/api/identity/resolve',
            method: 'GET',
            server: ['REMOTE_ADDR' => '192.168.1.100']
        );
        $request->attributes->set('_route', 'api_identity_resolve');

        $user = m::mock(UserInterface::class);
        $user->shouldReceive('getUserIdentifier')->andReturn('user-12345');

        $requestStack->shouldReceive('getCurrentRequest')->andReturn($request);
        $security->shouldReceive('getUser')->andReturn($user);

        $processor = new SecurityContextProcessor($requestStack, $security);

        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'security',
            level: Level::Debug,
            message: 'Authenticator set no success response: request continues.',
            context: ['authenticator' => 'CustomAuthenticator'],
            extra: ['existing' => 'value']
        );

        $processed = $processor($record);

        $this->assertSame('value', $processed->extra['existing']);
        $this->assertSame('GET', $processed->extra['http_method']);
        $this->assertSame('/api/identity/resolve', $processed->extra['path']);
        $this->assertSame('api_identity_resolve', $processed->extra['route']);
        $this->assertSame('192.168.1.100', $processed->extra['client_ip']);
        $this->assertSame('user-12345', $processed->extra['user']);
    }

    #[Test]
    public function it_handles_missing_request_and_missing_user(): void
    {
        $requestStack = m::mock(RequestStack::class);
        $security = m::mock(Security::class);

        $requestStack->shouldReceive('getCurrentRequest')->andReturnNull();
        $security->shouldReceive('getUser')->andReturnNull();

        $processor = new SecurityContextProcessor($requestStack, $security);

        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'app',
            level: Level::Info,
            message: 'CLI task executed',
            context: [],
            extra: []
        );

        $processed = $processor($record);

        $this->assertSame([], $processed->extra);
    }
}

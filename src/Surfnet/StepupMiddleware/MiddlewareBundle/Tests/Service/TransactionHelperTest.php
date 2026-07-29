<?php

/**
 * Copyright 2026 SURFnet bv
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

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\DBALConnectionHelper;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TransactionHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TransactionHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private Pipeline&MockInterface $pipeline;

    private BufferedEventBus&MockInterface $eventBus;

    private DBALConnectionHelper&MockInterface $connection;

    private TokenStorageInterface&MockInterface $tokenStorage;

    private TransactionHelper $transactionHelper;

    public function setUp(): void
    {
        $this->pipeline = m::mock(Pipeline::class);
        $this->eventBus = m::mock(BufferedEventBus::class);
        $this->connection = m::mock(DBALConnectionHelper::class);
        $this->tokenStorage = m::mock(TokenStorageInterface::class);

        $this->transactionHelper = new TransactionHelper(
            $this->pipeline,
            $this->eventBus,
            $this->connection,
            $this->tokenStorage,
        );
    }

    #[Test]
    public function begin_transaction_sets_a_fully_authorized_console_token_when_none_is_present(): void
    {
        $this->tokenStorage->shouldReceive('getToken')->once()->andReturn(null);
        $this->tokenStorage->shouldReceive('setToken')
            ->once()
            ->with(m::on(function (TokenInterface $token): bool {
                $roles = $token->getRoleNames();
                sort($roles);

                return $roles === ['ROLE_DEPROVISION', 'ROLE_MANAGEMENT', 'ROLE_RA', 'ROLE_SS'];
            }));

        $this->connection->shouldReceive('beginTransaction')->once();

        $this->transactionHelper->beginTransaction();
    }

    #[Test]
    public function begin_transaction_does_not_overwrite_an_existing_token(): void
    {
        $existingToken = m::mock(TokenInterface::class);

        $this->tokenStorage->shouldReceive('getToken')->once()->andReturn($existingToken);
        $this->tokenStorage->shouldNotReceive('setToken');

        $this->connection->shouldReceive('beginTransaction')->once();

        $this->transactionHelper->beginTransaction();
    }
}

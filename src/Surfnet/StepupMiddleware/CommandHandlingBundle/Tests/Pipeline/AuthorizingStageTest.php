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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Pipeline;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\ManagementExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\AuthorizingStage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizingStageTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    private MockInterface&AuthorizationCheckerInterface $authorizationChecker;

    private NullLogger $logger;

    public function setUp(): void
    {
        $this->logger = new NullLogger();
        $this->authorizationChecker = m::mock(
            AuthorizationCheckerInterface::class,
        );
    }

    #[Test]
    #[Group('pipeline')]
    public function when_a_command_has_no_marker_interface_authorization_is_granted_by_default(): void
    {
        $command = m::mock(AbstractCommand::class);
        $this->authorizationChecker->shouldNotHaveReceived('isGranted');

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);

        $this->assertInstanceOf(AuthorizingStage::class, $authorizingStage);
    }

    #[Test]
    #[DataProvider('interfaceToRoleMappingProvider')]
    #[Group('pipeline')]
    public function a_command_with_a_marker_interface_triggers_a_check_for_the_correct_role(
        string $interface,
        string $role,
    ): void {
        /** @var MockInterface&AbstractCommand $command */
        $command = m::mock(AbstractCommand::class . ', ' . $interface);
        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with($role)
            ->andReturn(true);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);

        $this->assertInstanceOf(AuthorizingStage::class, $authorizingStage);
    }

    #[Test]
    #[Group('pipeline')]
    public function when_a_command_implements_multiple_marker_interfaces_at_least_one_corresponding_role_is_required(): void
    {
        /** @var AbstractCommand&SelfServiceExecutable&RaExecutable&ManagementExecutable&MockInterface $command */
        $command = m::mock(
            sprintf(
                "%s, %s, %s, %s",
                AbstractCommand::class,
                SelfServiceExecutable::class,
                RaExecutable::class,
                ManagementExecutable::class
            )
        );

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with('ROLE_SS')
            ->andReturn(false);

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with('ROLE_RA')
            ->andReturn(true);

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with('ROLE_MANAGEMENT')
            ->andReturn(false);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);

        $this->assertInstanceOf(AuthorizingStage::class, $authorizingStage);
    }

    #[Test]
    #[Group('pipeline')]
    public function when_the_client_does_not_have_the_required_role_an_forbidden_exception_is_thrown(): void
    {
        $this->expectException(ForbiddenException::class);

        /** @var AbstractCommand&SelfServiceExecutable&MockInterface $command */
        $command = m::mock(
            sprintf(
                "%s, %s",
                AbstractCommand::class,
                SelfServiceExecutable::class,
            )
        );

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with('ROLE_SS')
            ->andReturn(false);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);

        $this->assertInstanceOf(AuthorizingStage::class, $authorizingStage);
    }

    public static function interfaceToRoleMappingProvider(): array
    {
        return [
            'SelfServiceExecutable => ROLE_SS' => [
                SelfServiceExecutable::class,
                'ROLE_SS',
            ],
            'RaExecutable => ROLE_RA' => [
                RaExecutable::class,
                'ROLE_RA',
            ],
            'ManagementExecutable => ROLE_MANAGEMENT' => [
                ManagementExecutable::class,
                'ROLE_MANAGEMENT',
            ],
        ];
    }
}

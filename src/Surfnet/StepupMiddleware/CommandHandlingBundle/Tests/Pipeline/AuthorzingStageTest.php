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
use PHPUnit_Framework_TestCase as UnitTest;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\AuthorizingStage;

class AuthorizingStageTest extends UnitTest
{
    /**
     * @var \Mockery\MockInterface mock of Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var NullLogger
     */
    private $logger;

    public function setUp()
    {
        $this->logger = new NullLogger();
        $this->authorizationChecker = m::mock(
            'Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface'
        );
    }

    /**
     * @test
     * @group pipeline
     */
    public function when_a_command_has_no_marker_interface_authorization_is_granted_by_default()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $this->authorizationChecker->shouldReceive('isGranted')->never();

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);
    }

    /**
     * @test
     * @group pipeline
     * @dataProvider interfaceToRoleMappingProvider
     *
     * @param string $interface
     * @param string $role
     */
    public function a_command_with_a_marker_interface_triggers_a_check_for_the_correct_role($interface, $role)
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command, ' . $interface);
        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with([$role])
            ->andReturn(true);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);
    }

    /**
     * @test
     * @group pipeline
     */
    public function when_a_command_implements_multiple_marker_interfaces_at_least_one_corresponding_role_is_required()
    {
        $command = m::mock(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command, '
            . 'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable, '
            . 'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable, '
            . 'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\ManagementExecutable'
        );

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with(['ROLE_SS'])
            ->andReturn(false);

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with(['ROLE_RA'])
            ->andReturn(true);

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->with(['ROLE_MANAGEMENT'])
            ->andReturn(false);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);
    }

    /**
     * @test
     * @group pipeline
     * @expectedException \Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\ForbiddenException
     */
    public function when_the_client_does_not_have_the_required_role_an_forbidden_exception_is_thrown()
    {
        $command = m::mock(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command, '
            . 'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable'
        );

        $this->authorizationChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with(['ROLE_SS'])
            ->andReturn(false);

        $authorizingStage = new AuthorizingStage($this->logger, $this->authorizationChecker);

        $authorizingStage->process($command);
    }

    public function interfaceToRoleMappingProvider()
    {
        return  [
            'SelfServiceExecutable => ROLE_SS' => [
                'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable',
                'ROLE_SS'
            ],
            'RaExecutable => ROLE_RA' => [
                'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable',
                'ROLE_RA'
            ],
            'ManagementExecutable => ROLE_MANAGEMENT' => [
                'Surfnet\StepupMiddleware\CommandHandlingBundle\Command\ManagementExecutable',
                'ROLE_MANAGEMENT'
            ]
        ];
    }
}

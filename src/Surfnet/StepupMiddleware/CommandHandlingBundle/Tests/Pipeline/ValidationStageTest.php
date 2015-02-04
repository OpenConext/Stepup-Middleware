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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\ValidationStage;

class ValidationStageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group pipeline
     */
    public function it_validates_commands()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $violations = m::mock('Symfony\Component\Validator\ConstraintViolationListInterface')
            ->shouldReceive('count')->with()->andReturn(0)
            ->getMock();
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->shouldReceive('validate')->once()->with($command)->andReturn($violations)
            ->getMock();

        $stage = new ValidationStage($validator);

        $this->assertSame($command, $stage->process($command));
    }

    /**
     * @test
     * @group pipeline
     */
    public function it_throws_an_exception_when_validation_fails()
    {
        $this->setExpectedException(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception\InvalidCommandException'
        );

        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $violations = m::mock('Symfony\Component\Validator\ConstraintViolationListInterface')
            ->shouldReceive('count')->with()->andReturn(1)
            ->shouldReceive('getIterator')->with()->andReturn(new \ArrayIterator())
            ->getMock();
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->shouldReceive('validate')->once()->with($command)->andReturn($violations)
            ->getMock();

        $stage = new ValidationStage($validator);
        $stage->process($command);
    }
}

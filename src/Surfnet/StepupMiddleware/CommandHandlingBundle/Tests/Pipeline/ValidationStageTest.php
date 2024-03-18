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

use ArrayIterator;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception\InvalidCommandException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\ValidationStage;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationStageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group pipeline
     */
    public function it_validates_commands(): void
    {
        $command = m::mock(Command::class);
        $violations = m::mock(ConstraintViolationListInterface::class)
            ->shouldReceive('count')->with()->andReturn(0)
            ->getMock();
        $validator = m::mock(ValidatorInterface::class)
            ->shouldReceive('validate')->once()->with($command)->andReturn($violations)
            ->getMock();

        $stage = new ValidationStage(new NullLogger(), $validator);

        $this->assertSame($command, $stage->process($command));
    }

    /**
     * @test
     * @group pipeline
     */
    public function it_throws_an_exception_when_validation_fails(): void
    {
        $this->expectException(InvalidCommandException::class);

        $command = m::mock(Command::class);
        $violations = m::mock(ConstraintViolationListInterface::class)
            ->shouldReceive('count')->with()->andReturn(1);
        $violations
            ->shouldReceive('getIterator')->with()->andReturn(new ArrayIterator())
            ->getMock();
        $validator = m::mock(ValidatorInterface::class)
            ->shouldReceive('validate')->once()->with($command)->andReturn($violations)
            ->getMock();

        $stage = new ValidationStage(new NullLogger(), $validator);
        $stage->process($command);
    }
}

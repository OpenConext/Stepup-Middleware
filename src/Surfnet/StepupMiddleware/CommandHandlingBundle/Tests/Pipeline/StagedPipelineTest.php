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
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\StagedPipeline;

class StagedPipelineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('pipeline')]
    public function it_passes_a_command_through_a_single_stage(): void
    {
        $command = m::mock(AbstractCommand::class);
        /** @var Stage&MockInterface $stage */
        $stage = m::mock(Stage::class)
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();

        $pipeline = new StagedPipeline(new NullLogger());
        $pipeline->addStage($stage);

        $this->assertSame($command, $pipeline->process($command));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('pipeline')]
    public function it_passes_a_command_through_multiple_stages(): void
    {
        $command = m::mock(AbstractCommand::class);
        /** @var Stage&MockInterface $stage1 */
        $stage1 = m::mock(Stage::class)
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();
        /** @var Stage&MockInterface $stage2 */
        $stage2 = m::mock(Stage::class)
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();

        $pipeline = new StagedPipeline(new NullLogger());
        $pipeline->addStage($stage1);
        $pipeline->addStage($stage2);

        $this->assertSame($command, $pipeline->process($command));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('pipeline')]
    public function it_passes_the_command_returned_from_an_earlier_stage_on_to_the_next(): void
    {
        $command1 = m::mock(AbstractCommand::class);
        $command2 = m::mock(AbstractCommand::class);
        /** @var Stage&MockInterface $stage1 */
        $stage1 = m::mock(Stage::class)
            ->shouldReceive('process')->once()->with($command1)->andReturn($command2)
            ->getMock();
        /** @var Stage&MockInterface $stage2 */
        $stage2 = m::mock(Stage::class)
            ->shouldReceive('process')->once()->with($command2)->andReturn($command2)
            ->getMock();

        $pipeline = new StagedPipeline(new NullLogger());
        $pipeline->addStage($stage1);
        $pipeline->addStage($stage2);

        $this->assertSame($command2, $pipeline->process($command1));
    }
}

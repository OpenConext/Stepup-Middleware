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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\StagedPipeline;

class StagedPipelineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group pipeline
     */
    public function it_passes_a_command_through_a_single_stage()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $stage = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage')
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();

        $pipeline = new StagedPipeline();
        $pipeline->addStage($stage);

        $this->assertSame($command, $pipeline->process($command));
    }

    /**
     * @test
     * @group pipeline
     */
    public function it_passes_a_command_through_multiple_stages()
    {
        $command = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $stage1 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage')
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();
        $stage2 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage')
            ->shouldReceive('process')->once()->with($command)->andReturn($command)
            ->getMock();

        $pipeline = new StagedPipeline();
        $pipeline->addStage($stage1);
        $pipeline->addStage($stage2);

        $this->assertSame($command, $pipeline->process($command));
    }

    /**
     * @test
     * @group pipeline
     */
    public function it_passes_the_command_returned_from_an_earlier_stage_on_to_the_next()
    {
        $command1 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $command2 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command');
        $stage1 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage')
            ->shouldReceive('process')->once()->with($command1)->andReturn($command2)
            ->getMock();
        $stage2 = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Stage')
            ->shouldReceive('process')->once()->with($command2)->andReturn($command2)
            ->getMock();

        $pipeline = new StagedPipeline();
        $pipeline->addStage($stage1);
        $pipeline->addStage($stage2);

        $this->assertSame($command2, $pipeline->process($command1));
    }
}

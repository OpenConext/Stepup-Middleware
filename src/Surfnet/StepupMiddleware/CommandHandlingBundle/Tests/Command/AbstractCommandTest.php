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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests;

use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;

/** Command object used for testing */
class TestNoopCommand extends AbstractCommand
{
}

class AbstractCommandTest extends UnitTest
{
    /**
     * @test
     * @group command
     */
    public function to_string_generates_command_identifiable_information()
    {
        $uuid = '1e8a8dc6-852e-4df8-ba23-8c18061b7c38'; // generated using Rhumsaa\Uuid\Uuid::uuid4();

        $command = new TestNoopCommand();
        $command->UUID = $uuid;

        $this->assertEquals(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\TestNoopCommand[' . $uuid . ']',
            (string) $command,
            'Command cast to string should give F\Q\C\N[UuidOfCommand]'
        );
    }
}

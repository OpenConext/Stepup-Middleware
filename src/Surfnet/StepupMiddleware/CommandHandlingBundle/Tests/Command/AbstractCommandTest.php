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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;

class AbstractCommandTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group command
     */
    public function to_string_generates_command_identifiable_information(): void
    {
        $command = new FixedUuidStubCommand();
        $uuid = $command->UUID;

        $this->assertEquals(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Command\FixedUuidStubCommand[' . $uuid . ']',
            (string)$command,
            'Command cast to string should give F\Q\C\N[UuidOfCommand]',
        );
    }
}

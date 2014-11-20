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

namespace Broadway\Domain;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\BroadwayFixedDateTimeNow;

/**
 * @runTestsInSeparateProcesses
 */
class BroadwayFixedDateTimeNowTest extends \PHPUnit_Framework_TestCase
{
    public function testItMocksMicrotime()
    {
        BroadwayFixedDateTimeNow::enable(new \DateTime('@12345'));

        $this->assertEquals(12345.0, microtime(true));
        $this->assertEquals('0.00000000 12345', microtime());
    }

    public function testItWorksWithSeparateProcesses()
    {
        $this->assertInternalType('float', microtime(true));
        $this->assertInternalType('string', microtime());
    }

    public function testItCanBeDisabledInTheSameProcess()
    {
        BroadwayFixedDateTimeNow::enable(new \DateTime('@12345'));
        $this->assertEquals(12345.0, microtime(true));

        BroadwayFixedDateTimeNow::disable();
        $this->assertNotEquals(12345.0, microtime(true));
        $this->assertInternalType('float', microtime(true));
        $this->assertInternalType('string', microtime());
    }

    public function testWeAreVisiting1970()
    {
        BroadwayFixedDateTimeNow::enable(new \DateTime('@0'));

        $this->assertEquals('1970-01-01T00:00:00.000000+00:00', DateTime::now()->toString());
    }
}

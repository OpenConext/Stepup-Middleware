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

use DateTime as CoreDateTime;
use Surfnet\Stepup\DateTime\DateTime;

/**
 * @runTestsInSeparateProcesses
 */
class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testItMocksNow()
    {
        DateTimeHelper::stubNow(new DateTime(new CoreDateTime('@12345')));

        $this->assertEquals(new DateTime(new CoreDateTime('@12345')), DateTime::now());
    }

    public function testItCanBeDisabledInTheSameProcess()
    {
        DateTimeHelper::stubNow(new DateTime(new CoreDateTime('@12345')));
        $this->assertEquals(new DateTime(new CoreDateTime('@12345')), DateTime::now());

        DateTimeHelper::stubNow(null);
        $this->assertTrue((new DateTime())->comesAfterOrIsEqual(DateTime::now()));
    }

    public function testItWorksWithSeparateProcesses()
    {
        // The stub value has been removed.
        $this->assertTrue((new DateTime())->comesAfterOrIsEqual(DateTime::now()));
    }
}

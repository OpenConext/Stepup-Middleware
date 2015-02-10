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

namespace Surfnet\Stepup\Tests\Identity\Value;

use DateTime as CoreDateTime;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class EmailVerificationWindowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group domain
     * @dataProvider invalidValueProvider
     *
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     */
    public function it_cannot_be_constructed_with_anything_but_integers($invalidValue)
    {
        EmailVerificationWindow::fromSeconds($invalidValue);
    }

    /**
     * @test
     * @group domain
     *
     * @runInSeparateProcess
     */
    public function window_is_open_for_instructed_amount_of_seconds_after_given_time()
    {
        // the valid window therefor is from 1 to 4 second (inclusive)
        $window = EmailVerificationWindow::fromSeconds(3);
        $startTime = new DateTime(new CoreDateTime('@1'));

        // before the starttime, window is not open
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@0')));
        $this->assertFalse($window->isOpen($startTime));

        // at the starttime, window is open
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@1')));
        $this->assertTrue($window->isOpen($startTime));

        // after the starttime but within the specified windowinterval, window is open
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@2')));
        $this->assertTrue($window->isOpen($startTime));

        // after the starttime but within the specified windowinterval, window is open
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@4')));
        $this->assertTrue($window->isOpen($startTime));

        // after the starttime and after the specified windowinterval, window is closed
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@5')));
        $this->assertFalse($window->isOpen($startTime));
    }

    /**
     * dataprovider
     */
    public function invalidValueProvider()
    {
        return [
            'empty string' => [''],
            'string'       => ['abc'],
            'array'        => [[]],
            'float'        => [2.718],
            'object'       => [new \StdClass()],
        ];
    }
}

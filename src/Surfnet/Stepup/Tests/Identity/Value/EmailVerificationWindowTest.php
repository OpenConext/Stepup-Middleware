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

use DateInterval;
use DateTime as CoreDateTime;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class EmailVerificationWindowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group domain
     *
     * @runInSeparateProcess
     */
    public function window_is_open_for_instructed_timeframe_after_given_time()
    {
        $startTime = new DateTime(new CoreDateTime('@1'));
        $timeFrame = TimeFrame::ofSeconds(3);

        $window = EmailVerificationWindow::createFromTimeFrameStartingAt($timeFrame, $startTime);

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@0')));
        $this->assertFalse($window->isOpen(), 'The window should not be open before the start time');

        // at the starttime, window is open
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@1')));
        $this->assertTrue($window->isOpen(), 'The window should be open at the start time');

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@2')));
        $this->assertTrue($window->isOpen(), 'The window should be open after the start time, before the end time');

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@4')));
        $this->assertTrue($window->isOpen(), 'The window should be open at the end time');

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@5')));
        $this->assertFalse($window->isOpen(), 'The window should be closed after the end time');
    }

    public function a_window_is_considered_equal_when_the_start_and_end_are_the_same()
    {
        // since we work with second precision, we might run issues trusting normal time, so we fixate the time
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@10000')));

        $base = $this->newEmailVerificationWindow(3);
        $same = $this->newEmailVerificationWindow(3);
        $earlier = $this->newEmailVerificationWindow(3);
//        $earlierOverlapping = $this->newEmailVerificationWindow()

    }

    private function newEmailVerificationWindow($timeFrameSeconds, $offsetIntervalSpec = null)
    {
        $start = DateTime::now();
        if ($offsetIntervalSpec) {
            $start = $start->add(new DateInterval($offsetIntervalSpec));
        }

        return EmailVerificationWindow::createFromTimeFrameStartingAt(
            TimeFrame::ofSeconds($timeFrameSeconds),
            $start
        );
    }
}

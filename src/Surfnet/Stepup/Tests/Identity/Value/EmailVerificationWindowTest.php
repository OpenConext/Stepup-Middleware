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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class EmailVerificationWindowTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group domain
     *
     * @runInSeparateProcess
     */
    public function window_is_open_for_instructed_timeframe_after_given_time(): void
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

    /**
     * @test
     * @group domain
     *
     * @runInSeparateProcess
     */
    public function a_window_is_considered_equal_when_the_start_and_end_are_the_same(): void
    {
        // since we work with second precision, we might run issues trusting normal time, so we fixate the time
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@10000')));

        $base = $this->newEmailVerificationWindow(3);
        $same = $this->newEmailVerificationWindow(3);
        $startsSameEndsEarlier = $this->newEmailVerificationWindow(2);
        $startsSameEndsLater = $this->newEmailVerificationWindow(4);
        $startsLater = $this->newEmailVerificationWindow(3, 'PT1S');
        $startsLaterEndsAtSameTime = $this->newEmailVerificationWindow(2, 'PT1S');
        $startsEarlier = $this->newEmailVerificationWindow(2, '-PT1S');
        $startsEarlierEndsAtSameTime = $this->newEmailVerificationWindow(4, '-PT1S');

        $this->assertTrue($base->equals($same));
        $this->assertFalse($base->equals($startsSameEndsEarlier));
        $this->assertFalse($base->equals($startsSameEndsLater));
        $this->assertFalse($base->equals($startsLater));
        $this->assertFalse($base->equals($startsLaterEndsAtSameTime));
        $this->assertFalse($base->equals($startsEarlier));
        $this->assertFalse($base->equals($startsEarlierEndsAtSameTime));
    }

    /**
     * @test
     * @group domain
     *
     * @runInSeparateProcess
     */
    public function the_window_correctly_calculates_the_end_datetime(): void
    {
        // since we work with second precision, we might run issues trusting normal time, so we fixate the time
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@10')));

        $window = EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(3), DateTime::now());

        $endTime = $window->openUntil();
        $this->assertEquals(new DateTime(new CoreDateTime('@13')), $endTime);

        $window = EmailVerificationWindow::createWindowFromTill(
            DateTime::now(),
            DateTime::now()->add(new DateInterval('PT3S')),
        );
        $endTimeTwo = $window->openUntil();
        $this->assertEquals(new DateTime(new CoreDateTime('@13')), $endTimeTwo);
    }

    /**
     * Helper method for easy EmailVerificationWindow creation
     *
     * @param string|null $startTimeOffset
     * @return EmailVerificationWindow
     */
    private function newEmailVerificationWindow(int $timeFrameSeconds, $startTimeOffset = null)
    {
        $start = DateTime::now();
        if ($startTimeOffset) {
            if (str_starts_with($startTimeOffset, '-')) {
                $offset = substr($startTimeOffset, 1);
                $start = $start->sub(new DateInterval($offset));
            } else {
                $start = $start->add(new DateInterval($startTimeOffset));
            }
        }

        return EmailVerificationWindow::createFromTimeFrameStartingAt(
            TimeFrame::ofSeconds($timeFrameSeconds),
            $start,
        );
    }
}

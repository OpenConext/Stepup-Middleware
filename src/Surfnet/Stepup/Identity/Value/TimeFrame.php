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

namespace Surfnet\Stepup\Identity\Value;

use DateInterval;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class TimeFrame
{
    /**
     * @var DateInterval
     */
    private $timeFrame;

    /**
     * @param DateInterval $timeFrame
     */
    final private function __construct(DateInterval $timeFrame)
    {
        $this->timeFrame = $timeFrame;
    }

    /**
     * @param int $seconds
     * @return TimeFrame
     */
    public static function ofSeconds($seconds)
    {
        if (!is_int($seconds) || $seconds < 1) {
            throw InvalidArgumentException::invalidType('positive integer', 'seconds', $seconds);
        }

        return new TimeFrame(new DateInterval('PT' . $seconds . 'S'));
    }

    /**
     * @param DateTime $dateTime
     * @return DateTime
     */
    public function getEndWhenStartingAt(DateTime $dateTime)
    {
        return $dateTime->add($this->timeFrame);
    }

    /**
     * @param TimeFrame $other
     * @return bool
     */
    public function equals(TimeFrame $other)
    {
        return $this->timeFrame->s === $other->timeFrame->s;
    }

    public function __toString()
    {
        return $this->timeFrame->format('%S');
    }
}

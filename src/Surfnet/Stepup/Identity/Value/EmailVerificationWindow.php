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

class EmailVerificationWindow
{
    /**
     * @var DateInterval
     */
    private $interval;

    private function __construct(DateInterval $interval)
    {
        $this->interval = $interval;
    }

    /**
     * @param int $seconds
     * @return EmailVerificationWindow
     */
    public static function fromSeconds($seconds)
    {
        if (!is_int($seconds)) {
            throw InvalidArgumentException::invalidType('int', 'seconds', $seconds);
        }

        return new EmailVerificationWindow(new DateInterval('PT' . $seconds . 'S'));
    }

    /**
     * @param DateTime $windowStartedAt
     * @return bool
     */
    public function isOpen(DateTime $windowStartedAt)
    {
        $now = DateTime::now();
        $till = $windowStartedAt->add($this->interval);

        return !$now->comesAfter($till) && !$now->comesBefore($windowStartedAt);
    }

    /**
     * @param EmailVerificationWindow $otherWindow
     * @return bool
     */
    public function equals(EmailVerificationWindow $otherWindow)
    {
        return $this->interval->s === $otherWindow->interval->s;
    }

    /**
     * Do note that this returns the amount of seconds, not so much a human readable format.
     */
    public function __toString()
    {
        return $this->interval->format('S');
    }
}

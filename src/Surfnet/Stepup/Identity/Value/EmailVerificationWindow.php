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

use Surfnet\Stepup\DateTime\DateTime;

class EmailVerificationWindow
{
    /**
     * @var DateTime
     */
    private $start;

    /**
     * @var DateTime
     */
    private $end;

    private function __construct(DateTime $start, DateTime $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param TimeFrame $timeFrame
     * @param DateTime  $start
     * @return EmailVerificationWindow
     */
    public static function createFromTimeFrameStartingAt(TimeFrame $timeFrame, DateTime $start)
    {
        return new EmailVerificationWindow($start, $timeFrame->getEndWhenStartingAt($start));
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        $now = DateTime::now();

        return $now->comesAfterOrIsEqual($this->start) && $now->comesBeforeOrIsEqual($this->end);
    }

    /**
     * @param EmailVerificationWindow $other
     * @return bool
     */
    public function equals(EmailVerificationWindow $other)
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public function __toString()
    {
        return $this->start . ' - ' . $this->end;
    }
}

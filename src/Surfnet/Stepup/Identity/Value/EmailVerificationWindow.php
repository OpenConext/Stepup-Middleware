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

use Broadway\Serializer\Serializable as SerializableInterface;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class EmailVerificationWindow implements SerializableInterface
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
     * @param DateTime $start
     * @param DateTime $end
     * @return EmailVerificationWindow
     */
    public static function createWindowFromTill(DateTime $start, DateTime $end)
    {
        if (!$end->comesAfter($start)) {
            throw new InvalidArgumentException(sprintf(
                'An EmailVerificationWindow can only be created with an end time that is after the start time, '
                . 'given start: "%s", given end: "%s"',
                (string) $start,
                (string) $end
            ));
        }

        return new EmailVerificationWindow($start, $end);
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
     * @return DateTime
     */
    public function openUntil()
    {
        return $this->end;
    }

    /**
     * @param EmailVerificationWindow $other
     * @return bool
     */
    public function equals(EmailVerificationWindow $other)
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public static function deserialize(array $data)
    {
        return new EmailVerificationWindow(
            DateTime::fromString($data['start']),
            DateTime::fromString($data['end'])
        );
    }

    public function serialize(): array
    {
        return ['start' => (string) $this->start, 'end' => (string) $this->end];
    }

    public function __toString()
    {
        return $this->start . '-' . $this->end;
    }
}

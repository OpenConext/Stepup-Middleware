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
use Stringable;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final readonly class EmailVerificationWindow implements SerializableInterface, Stringable
{
    private function __construct(
        private DateTime $start,
        private DateTime $end,
    ) {
    }

    public static function createFromTimeFrameStartingAt(TimeFrame $timeFrame, DateTime $start): EmailVerificationWindow
    {
        return new EmailVerificationWindow($start, $timeFrame->getEndWhenStartingAt($start));
    }

    public static function createWindowFromTill(DateTime $start, DateTime $end): EmailVerificationWindow
    {
        if (!$end->comesAfter($start)) {
            throw new InvalidArgumentException(
                sprintf(
                    'An EmailVerificationWindow can only be created with an end time that is after the start time, '
                    . 'given start: "%s", given end: "%s"',
                    (string)$start,
                    (string)$end,
                ),
            );
        }

        return new EmailVerificationWindow($start, $end);
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        $now = DateTime::now();

        return $now->comesAfterOrIsEqual($this->start) && $now->comesBeforeOrIsEqual($this->end);
    }

    /**
     * @return DateTime
     */
    public function openUntil(): DateTime
    {
        return $this->end;
    }

    public function equals(EmailVerificationWindow $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public static function deserialize(array $data): self
    {
        return new EmailVerificationWindow(
            DateTime::fromString($data['start']),
            DateTime::fromString($data['end']),
        );
    }

    public function serialize(): array
    {
        return ['start' => (string)$this->start, 'end' => (string)$this->end];
    }

    public function __toString(): string
    {
        return $this->start . '-' . $this->end;
    }
}

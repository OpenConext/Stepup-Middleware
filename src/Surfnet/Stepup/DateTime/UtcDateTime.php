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

namespace Surfnet\Stepup\DateTime;

use DateInterval;
use DateTime as CoreDateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class UtcDateTime
{
    /**
     * The 'c' format, expanded in separate format characters. This string can also be used with
     * `DateTime::createFromString()`.
     */
    const FORMAT = 'Y-m-d\\TH:i:sP';

    /**
     * @var CoreDateTime
     */
    private $dateTime;

    /**
     * @param CoreDateTime $dateTime
     */
    public function __construct(CoreDateTime $dateTime)
    {
        if ($dateTime->getOffset() !== 0) {
            throw new InvalidArgumentException(
                'Stepup DateTime requires a UTC datetime, but got DateTime with offset %d',
                $dateTime->getOffset()
            );
        }

        $this->dateTime = $dateTime;
    }

    /**
     * @param DateInterval $interval
     * @return UtcDateTime
     */
    public function add(DateInterval $interval)
    {
        $dateTime = clone $this->dateTime;
        $dateTime->add($interval);

        return new self($dateTime);
    }

    /**
     * @param DateInterval $interval
     * @return UtcDateTime
     */
    public function sub(DateInterval $interval)
    {
        $dateTime = clone $this->dateTime;
        $dateTime->sub($interval);

        return new self($dateTime);
    }

    /**
     * @param UtcDateTime $dateTime
     * @return boolean
     */
    public function comesBefore(UtcDateTime $dateTime)
    {
        return $this->dateTime < $dateTime->dateTime;
    }

    /**
     * @param UtcDateTime $dateTime
     * @return boolean
     */
    public function comesBeforeOrIsEqual(UtcDateTime $dateTime)
    {
        return $this->dateTime <= $dateTime->dateTime;
    }

    /**
     * @param UtcDateTime $dateTime
     * @return boolean
     */
    public function comesAfter(UtcDateTime $dateTime)
    {
        return $this->dateTime > $dateTime->dateTime;
    }

    /**
     * @param UtcDateTime $dateTime
     * @return boolean
     */
    public function comesAfterOrIsEqual(UtcDateTime $dateTime)
    {
        return $this->dateTime >= $dateTime->dateTime;
    }

    /**
     * @param $format
     * @return string
     */
    public function format($format)
    {
        $formatted = $this->dateTime->format($format);

        if ($formatted === false) {
            throw new InvalidArgumentException(sprintf(
                'Given format "%s" is not a valid format for DateTime',
                $format
            ));
        }

        return $formatted;
    }

    /**
     * @return string An ISO 8601 representation of this DateTime.
     */
    public function __toString()
    {
        return $this->format(self::FORMAT);
    }
}

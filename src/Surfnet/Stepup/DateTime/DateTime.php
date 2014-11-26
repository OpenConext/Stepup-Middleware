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

use DateTimeImmutable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class DateTime extends DateTimeImmutable
{
    /**
     * The 'c' format, expanded in separate format characters. This string can also be used with
     * `DateTime::createFromString()`.
     */
    const FORMAT = "Y-m-d\\TH:i:sP";

    /**
     * Allows for mocking of time.
     *
     * @var self|null
     */
    private static $now;

    /**
     * @return self
     */
    public static function now()
    {
        return self::$now ?: new self;
    }

    /**
     * @param string $dateTime A date-time string formatted using `self::FORMAT` (eg. '2014-11-26T15:20:43+01:00').
     * @return self
     */
    public static function fromString($dateTime)
    {
        if (!is_string($dateTime)) {
            InvalidArgumentException::invalidType('string', 'dateTime', $dateTime);
        }

        return self::createFromFormat(self::FORMAT, $dateTime);
    }

    /**
     * @return string An ISO 8601 representation of this DateTime.
     */
    public function __toString()
    {
        return $this->format(self::FORMAT);
    }
}

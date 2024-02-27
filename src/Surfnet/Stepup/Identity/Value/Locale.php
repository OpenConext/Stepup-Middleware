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

use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class Locale implements JsonSerializable
{
    private string $locale;

    /**
     * @param string $locale
     */
    public function __construct($locale)
    {
        if (!is_string($locale)) {
            throw InvalidArgumentException::invalidType('string', 'locale', $locale);
        }

        $this->locale = $locale;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function equals(Locale $other): bool
    {
        return $this == $other;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    public function jsonSerialize()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->locale;
    }
}

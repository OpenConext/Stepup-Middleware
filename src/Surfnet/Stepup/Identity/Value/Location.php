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

class Location implements JsonSerializable
{
    /**
     * @var string
     */
    private $location;

    /**
     * @param string $location
     */
    public function __construct($location)
    {
        if (!is_string($location)) {
            throw InvalidArgumentException::invalidType('string', 'location', $location);
        }

        $this->location = trim($location);
    }

    /**
     * @param Location $otherLocation
     * @return bool
     */
    public function equals(Location $otherLocation)
    {
        return $this->location === $otherLocation->location;
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }

    public function __toString()
    {
        return $this->location;
    }
}

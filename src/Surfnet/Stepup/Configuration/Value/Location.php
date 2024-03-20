<?php

declare(strict_types=1);

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Value;

use JsonSerializable;
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class Location implements JsonSerializable, Stringable
{
    private readonly string $location;

    /**
     * @param string $location
     */
    public function __construct(string $location)
    {
        if (!is_string($location)) {
            throw InvalidArgumentException::invalidType('string', 'location', $location);
        }

        $this->location = trim($location);
    }

    public function equals(Location $otherLocation): bool
    {
        return $this->location === $otherLocation->location;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    public function __toString(): string
    {
        return $this->location;
    }

    public function jsonSerialize(): string
    {
        return $this->location;
    }
}

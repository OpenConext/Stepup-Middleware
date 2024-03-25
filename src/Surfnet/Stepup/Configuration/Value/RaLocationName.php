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

final class RaLocationName implements JsonSerializable, Stringable
{
    private readonly string $raLocationName;

    public function __construct(string $raLocationName)
    {
        if (trim($raLocationName) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'raLocationName', $raLocationName);
        }

        $this->raLocationName = $raLocationName;
    }

    public function equals(RaLocationName $otherRaLocationName): bool
    {
        return $this->raLocationName === $otherRaLocationName->raLocationName;
    }

    /**
     * @return string
     */
    public function getRaLocationName(): string
    {
        return $this->raLocationName;
    }

    public function __toString(): string
    {
        return $this->raLocationName;
    }

    public function jsonSerialize(): string
    {
        return $this->raLocationName;
    }
}

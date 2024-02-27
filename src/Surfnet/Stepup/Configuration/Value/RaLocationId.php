<?php

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
use Ramsey\Uuid\Uuid;
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class RaLocationId implements JsonSerializable, Stringable
{
    private readonly string $raLocationId;

    /**
     * @param string $raLocationId
     */
    public function __construct($raLocationId)
    {
        if (!is_string($raLocationId) || trim($raLocationId) === '') {
            throw InvalidArgumentException::invalidType(
                'non-empty string',
                'raLocationId',
                $raLocationId,
            );
        }

        if (!Uuid::isValid($raLocationId)) {
            throw InvalidArgumentException::invalidType(
                'UUID',
                'raLocationId',
                $raLocationId,
            );
        }

        $this->raLocationId = $raLocationId;
    }

    /**
     * @return bool
     */
    public function equals(RaLocationId $otherRaLocationId): bool
    {
        return $this->raLocationId === $otherRaLocationId->raLocationId;
    }

    /**
     * @return string
     */
    public function getRaLocationId()
    {
        return $this->raLocationId;
    }

    public function __toString(): string
    {
        return $this->raLocationId;
    }

    public function jsonSerialize(): string
    {
        return $this->raLocationId;
    }
}

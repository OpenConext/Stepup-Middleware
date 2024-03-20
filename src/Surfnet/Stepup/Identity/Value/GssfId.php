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

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class GssfId implements SecondFactorIdentifier
{
    private readonly string $gssfId;

    public static function unknown(): static
    {
        return new self('—');
    }

    public function __construct($gssfId)
    {
        if (!is_string($gssfId) || trim($gssfId) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'gssfId', $gssfId);
        }

        $this->gssfId = trim($gssfId);
    }

    public function getValue(): string
    {
        return $this->gssfId;
    }

    public function __toString(): string
    {
        return $this->gssfId;
    }

    public function equals(SecondFactorIdentifier $other): bool
    {
        return $other instanceof self && $this->gssfId === $other->gssfId;
    }

    public function jsonSerialize(): string
    {
        return $this->gssfId;
    }
}

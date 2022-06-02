<?php

/**
 * Copyright 2022 SURFnet bv
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

/**
 * Marker recovery token identifier for the SafeStore token type
 */
final class SafeStore implements RecoveryTokenIdentifier
{
    private $hashedSecret;


    public function __construct(string $hashedSecret)
    {
        $this->hashedSecret = $hashedSecret;
    }


    public static function unknown(): self
    {
        return new self('');
    }

    public function getValue()
    {
        return $this->hashedSecret;
    }

    public function equals($other): bool
    {
        return $other instanceof self && $other->getValue() === $this->getValue();
    }

    public function __toString(): string
    {
        return $this->hashedSecret;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}

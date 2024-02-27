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

final class PhoneNumber implements SecondFactorIdentifier, RecoveryTokenIdentifier
{
    private readonly string $phoneNumber;

    public static function unknown(): self
    {
        return new self('+0 (0) 000000000');
    }

    public function __construct($phoneNumber)
    {
        if (!is_string($phoneNumber)) {
            throw InvalidArgumentException::invalidType('string', 'value', $phoneNumber);
        }

        if (!preg_match('~^\+[\d\s]+ \(0\) \d+$~', $phoneNumber)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid phone number format, expected +{countryCode} (0) {subscriber}, got '%s...' (truncated)",
                    // 12 characters captures the most extended country code up to and incl. the first subscriber digit
                    substr($phoneNumber, 0, 12),
                ),
            );
        }

        $this->phoneNumber = $phoneNumber;
    }

    public function getValue()
    {
        return $this->phoneNumber;
    }

    public function equals($other): bool
    {
        return $other instanceof self && $this->phoneNumber === $other->phoneNumber;
    }

    public function __toString(): string
    {
        return $this->phoneNumber;
    }

    public function jsonSerialize()
    {
        return $this->phoneNumber;
    }
}

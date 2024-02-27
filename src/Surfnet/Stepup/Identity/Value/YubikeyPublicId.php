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

final class YubikeyPublicId implements SecondFactorIdentifier
{
    public const UNKNOWN = '—';

    private string $value;

    public static function unknown(): self
    {
        return new self(self::UNKNOWN);
    }

    public function __construct($value)
    {
        if ($value === self::UNKNOWN) {
            $this->value = $value;
            return;
        }

        if (!is_string($value)) {
            throw InvalidArgumentException::invalidType('string', 'value', $value);
        }

        // Numeric IDs must be left-padded with zeroes until eight characters. Longer IDs, up to twenty characters, may
        // not be padded.
        if (!preg_match('~^\d{8,20}$~', $value)) {
            throw new InvalidArgumentException('Given Yubikey public ID is not a string of 8 to 20 digits');
        }
        if ($value !== sprintf('%08s', ltrim($value, '0'))) {
            throw new InvalidArgumentException(
                'Given Yubikey public ID is longer than 8 digits, yet left-padded with zeroes',
            );
        }

        // Yubikey public IDs, in their (mod)hex format, may be up to sixteen characters in length. Thus, this is their
        // maximum value.
        if (gmp_cmp(gmp_init($value, 10), gmp_init('ffffffffffffffff', 16)) > 0) {
            throw new InvalidArgumentException('Given Yubikey public ID is larger than 0xffffffffffffffff');
        }

        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals($other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function jsonSerialize()
    {
        return $this->value;
    }
}

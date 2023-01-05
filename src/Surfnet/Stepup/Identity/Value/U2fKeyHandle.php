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

/**
 * @deprecated Built in U2F support is dropped from StepUp, this was not removed to support event replay
 */
final class U2fKeyHandle implements SecondFactorIdentifier
{
    const UNKNOWN = '—';

    /**
     * @var string
     */
    private $value;

    /**
     * @return static
     */
    public static function unknown()
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

        if (empty($value)) {
            throw new InvalidArgumentException('Invalid Argument, parameter "value" may not be an empty string');
        }

        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
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

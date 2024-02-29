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
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

/**
 * The natural identifier of an Identity is the SAML Name ID.
 */
final class NameId implements JsonSerializable, Stringable
{
    /**
     * This length reflects the maximum length supported by the data store for the
     * name id field.
     *
     * Not to be confused by the soft limit described in the SAML2 specification.
     */
    private const MAX_LENGTH = 255;

    private readonly string $value;

    public function __construct($value)
    {
        if (!is_string($value)) {
            throw InvalidArgumentException::invalidType('string', 'value', $value);
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Invalid argument type: maximum length for nameId exceeds configured length of ' . self::MAX_LENGTH,
            );
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getNameId()
    {
        return $this->value;
    }

    public function equals($other): bool
    {
        return $this == $other;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}

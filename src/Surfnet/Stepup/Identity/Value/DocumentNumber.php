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

final class DocumentNumber implements JsonSerializable, Stringable
{
    private readonly string $documentNumber;

    /**
     * @return self
     */
    public static function unknown(): self
    {
        return new self('—');
    }

    /**
     * @param string $documentNumber
     */
    public function __construct($documentNumber)
    {
        if (!is_string($documentNumber) || ($documentNumber === '' || $documentNumber === '0')) {
            throw InvalidArgumentException::invalidType('non-empty string', 'documentNumber', $documentNumber);
        }

        $this->documentNumber = $documentNumber;
    }

    /**
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    public function __toString(): string
    {
        return $this->documentNumber;
    }

    public function equals(DocumentNumber $other): bool
    {
        return $this->documentNumber === $other->documentNumber;
    }

    public function jsonSerialize(): string
    {
        return $this->documentNumber;
    }
}

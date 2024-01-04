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
use Surfnet\Stepup\Exception\InvalidArgumentException;

use function preg_match;
use function strval;

final class DocumentNumber implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $documentNumber;

    /**
     * @return self
     */
    public static function unknown(): self
    {
        return new self(null);
    }

    /**
     * @param string|null $documentNumber
     */
    public function __construct(?string $documentNumber)
    {
        if ($documentNumber === null) {
            // Created using the static ::unknown method
        } elseif (empty($documentNumber)) {
            throw InvalidArgumentException::invalidType('non-empty string', 'documentNumber', $documentNumber);
        } elseif (!preg_match('/^([-]|[A-Z0-9-]{6})$/i', $documentNumber)) {
            throw InvalidArgumentException::invalidType('valid characters', 'documentNumber', $documentNumber);
        }

        $this->documentNumber = $documentNumber;
    }

    /**
     * @return string|null
     */
    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function __toString()
    {
        return strval($this->documentNumber);
    }

    public function equals(DocumentNumber $other): bool
    {
        return $this->documentNumber === $other->documentNumber;
    }

    public function jsonSerialize()
    {
        return $this->documentNumber;
    }
}

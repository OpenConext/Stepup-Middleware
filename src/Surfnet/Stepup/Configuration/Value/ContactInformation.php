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
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class ContactInformation implements JsonSerializable
{
    private string $contactInformation;

    /**
     * @param string $contactInformation
     */
    public function __construct($contactInformation)
    {
        if (!is_string($contactInformation)) {
            throw InvalidArgumentException::invalidType('string', 'contactInformation', $contactInformation);
        }

        $this->contactInformation = trim($contactInformation);
    }

    /**
     * @param ContactInformation $otherContactInformation
     * @return bool
     */
    public function equals(ContactInformation $otherContactInformation): bool
    {
        return $this->contactInformation === $otherContactInformation->contactInformation;
    }

    /**
     * @return string
     */
    public function getContactInformation()
    {
        return $this->contactInformation;
    }

    public function __toString(): string
    {
        return $this->contactInformation;
    }

    public function jsonSerialize()
    {
        return $this->contactInformation;
    }
}

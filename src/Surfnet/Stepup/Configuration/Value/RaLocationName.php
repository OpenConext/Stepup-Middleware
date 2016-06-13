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

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class RaLocationName
{
    /**
     * @var string
     */
    private $raLocationName;

    /**
     * @param string $raLocationName
     */
    public function __construct($raLocationName)
    {
        if (!is_string($raLocationName) || trim($raLocationName) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'raLocationName', $raLocationName);
        }

        $this->raLocationName = $raLocationName;
    }

    /**
     * @param RaLocationName $otherRaLocationName
     * @return bool
     */
    public function equals(RaLocationName $otherRaLocationName)
    {
        return $this->raLocationName === $otherRaLocationName->raLocationName;
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }

    public function __toString()
    {
        return $this->raLocationName;
    }
}

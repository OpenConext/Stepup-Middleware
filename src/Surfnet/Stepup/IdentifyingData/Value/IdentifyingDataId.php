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

namespace Surfnet\Stepup\IdentifyingData\Value;

use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class IdentifyingDataId
{
    /**
     * @var string
     */
    private $identifyingDataId;

    /**
     * @param string $identifyingDataId
     */
    public function __construct($identifyingDataId)
    {
        if (!is_string($identifyingDataId) || trim($identifyingDataId) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'identifyingDataId', $identifyingDataId);
        }

        $this->identifyingDataId = $identifyingDataId;
    }

    /**
     * @return IdentifyingDataId
     */
    public static function generate()
    {
        return new self(Uuid::uuid4());
    }

    /**
     * @param IdentifyingDataId $other
     * @return bool
     */
    public function equals(IdentifyingDataId $other)
    {
        return $this->identifyingDataId === $other->identifyingDataId;
    }

    public function __toString()
    {
        return $this->identifyingDataId;
    }
}

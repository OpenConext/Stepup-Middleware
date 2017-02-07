<?php

/**
 * Copyright 2017 SURFnet B.V.
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

final class SecondFactor implements JsonSerializable
{
    /**
     * @var string
     */
    private $secondFactor;

    /**
     * @param string $secondFactor
     */
    public function __construct($secondFactor)
    {
        if (!is_string($secondFactor)) {
            throw InvalidArgumentException::invalidType('string', 'secondFactor', $secondFactor);
        }

        $this->secondFactor = $secondFactor;
    }

    /**
     * @param SecondFactor $other
     * @return bool
     */
    public function equals(self $other)
    {
        return $this->secondFactor === $other->secondFactor;
    }

    /**
     * @return string
     */
    public function getSecondFactor()
    {
        return $this->secondFactor;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->secondFactor;
    }

    public function jsonSerialize()
    {
        return $this->secondFactor;
    }
}

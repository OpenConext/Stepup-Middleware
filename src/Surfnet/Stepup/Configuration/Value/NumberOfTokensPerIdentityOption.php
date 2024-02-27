<?php

/**
 * Copyright 2018 SURFnet B.V.
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

class NumberOfTokensPerIdentityOption implements JsonSerializable
{
    const DISABLED = 0;
    
    /**
     * @var int
     */
    private $numberOfTokensPerIdentity;

    public static function getDefault(): self
    {
        return new self(self::DISABLED);
    }

    public function __construct($numberOfTokensPerIdentity)
    {
        if (!is_numeric($numberOfTokensPerIdentity)) {
            throw InvalidArgumentException::invalidType(
                'integer',
                'numberOfTokensPerIdentity',
                $numberOfTokensPerIdentity
            );
        }

        $this->numberOfTokensPerIdentity = $numberOfTokensPerIdentity;
    }

    /**
     * @param NumberOfTokensPerIdentityOption $other
     * @return bool
     */
    public function equals(NumberOfTokensPerIdentityOption $other): bool
    {
        return $this->numberOfTokensPerIdentity === $other->numberOfTokensPerIdentity;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->numberOfTokensPerIdentity > self::DISABLED;
    }

    /**
     * @return int
     */
    public function getNumberOfTokensPerIdentity()
    {
        return $this->numberOfTokensPerIdentity;
    }

    public function jsonSerialize()
    {
        return $this->numberOfTokensPerIdentity;
    }
}

<?php

/**
 * Copyright 2018 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not select this file except in compliance with the License.
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

class SelectRaaOption implements JsonSerializable
{
    /**
     * @var string[]|null
     */
    private $institutions;

    /**
     * SelectRaaOption constructor.
     * @param string[]|null $selectRaaOption
     */
    public function __construct($selectRaaOption)
    {
        if (!is_null($selectRaaOption) && !is_array($selectRaaOption)) {
            throw InvalidArgumentException::invalidType(
                'null or string[]',
                'selectRaaOption',
                $selectRaaOption
            );
        }

        $this->institutions = $selectRaaOption;

        // Sort the array values alphabetically
        if (is_array($this->institutions)) {
            sort($this->institutions);
        }
    }

    public static function getDefault()
    {
        return new self(null);
    }

    public function getInstitutions()
    {
        return $this->institutions;
    }

    public function equals(SelectRaaOption $other)
    {
        return $this->getInstitutions() === $other->getInstitutions();
    }

    public function jsonSerialize()
    {
        return $this->getInstitutions();
    }
}

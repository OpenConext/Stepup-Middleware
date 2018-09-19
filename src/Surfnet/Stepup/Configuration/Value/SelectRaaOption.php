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
    private $selectRaaOption;

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
        $this->selectRaaOption = $selectRaaOption;
    }

    public static function getDefault()
    {
        return new self(null);
    }

    /**
     * If array, returns the array sorted
     * @return null|string[]
     */
    public function getSelectRaaOption()
    {
        if (is_array($this->selectRaaOption)) {
            sort($this->selectRaaOption);
        }
        return $this->selectRaaOption;
    }

    public function equals(SelectRaaOption $other)
    {
        return $this->getSelectRaaOption() === $other->getSelectRaaOption();
    }

    public function jsonSerialize()
    {
        return $this->getSelectRaaOption();
    }
}
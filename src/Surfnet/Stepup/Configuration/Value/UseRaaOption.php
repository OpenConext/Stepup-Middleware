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

class UseRaaOption implements JsonSerializable
{
    /**
     * @var array
     */
    private $useRaaOption;

    public function __construct(array $useRaaOption)
    {
        if (!is_null($useRaaOption) && !is_array($useRaaOption)) {
            throw InvalidArgumentException::invalidType(
                'null or string[]',
                'useRaaOption',
                $useRaaOption
            );
        }
        $this->useRaaOption = $useRaaOption;
    }

    public function getUseRaaOption()
    {
        return $this->useRaaOption;
    }

    public function jsonSerialize()
    {
        return $this->getUseRaaOption();
    }
}

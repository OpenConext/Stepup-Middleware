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

final class ShowRaaContactInformationOption implements JsonSerializable
{
    /**
     * @var bool
     */
    private $showRaaContactInformationOption;

    public static function getDefault()
    {
        return new self(true);
    }

    public function __construct($showRaaContactInformationOption)
    {
        if (!is_bool($showRaaContactInformationOption)) {
            throw InvalidArgumentException::invalidType(
                'boolean',
                'showRaaContactInformationOption',
                $showRaaContactInformationOption
            );
        }

        $this->showRaaContactInformationOption = $showRaaContactInformationOption;
    }

    /**
     * @param ShowRaaContactInformationOption $other
     * @return bool
     */
    public function equals(ShowRaaContactInformationOption $other)
    {
        return $this->showRaaContactInformationOption === $other->showRaaContactInformationOption;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->showRaaContactInformationOption;
    }

    public function jsonSerialize()
    {
        return $this->showRaaContactInformationOption;
    }
}

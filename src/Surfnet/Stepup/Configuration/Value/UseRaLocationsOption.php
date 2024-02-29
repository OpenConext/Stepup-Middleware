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

final class UseRaLocationsOption implements JsonSerializable
{
    private readonly bool $useRaLocationsOption;

    /**
     * @return UseRaLocationsOption
     */
    public static function getDefault(): self
    {
        return new self(false);
    }

    public function __construct($useRaLocationsOption)
    {
        if (!is_bool($useRaLocationsOption)) {
            throw InvalidArgumentException::invalidType(
                'boolean',
                'useRaLocationsOption',
                $useRaLocationsOption,
            );
        }

        $this->useRaLocationsOption = $useRaLocationsOption;
    }

    /**
     * @return bool
     */
    public function equals(UseRaLocationsOption $other): bool
    {
        return $this->useRaLocationsOption === $other->useRaLocationsOption;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->useRaLocationsOption;
    }

    public function jsonSerialize(): bool
    {
        return $this->useRaLocationsOption;
    }
}

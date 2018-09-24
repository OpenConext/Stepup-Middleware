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

class UseRaOption implements JsonSerializable
{
    /**
     * @var InstitutionSet|null
     */
    private $institutions;

    /**
     * UseRaOption constructor.
     * @param null|array $useRaOption
     */
    public function __construct($useRaOption)
    {
        if (!is_null($useRaOption) && !is_array($useRaOption)) {
            throw InvalidArgumentException::invalidType(
                'null or string[]',
                'useRaOption',
                $useRaOption
            );

        }
        // Sort the array values alphabetically
        if (is_array($useRaOption)) {
            sort($useRaOption);
            $this->institutions = InstitutionSet::fromInstitutionConfig($useRaOption);
        }
    }

    public static function getDefault()
    {
        return new self(null);
    }

    public function equals(UseRaOption $other)
    {
        $thisValue = null;
        $otherValue = null;
        if (!is_null($this->getInstitutions())) {
            $thisValue = $this->getInstitutions()->toScalarArray();
        }
        if (!is_null($other->getInstitutions())) {
            $otherValue = $other->getInstitutions()->toScalarArray();
        }
        return $thisValue === $otherValue;
    }

    public function getInstitutions()
    {
        return $this->institutions;
    }

    public function jsonSerialize()
    {
        return $this->getInstitutions()->toScalarArray();
    }
}

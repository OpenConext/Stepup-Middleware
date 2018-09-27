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

final class InstitutionSet implements JsonSerializable
{
    /**
     * @var Institution[]
     */
    private $institutions;

    /**
     * @param Institution[]
     */
    private function __construct(array $institutions)
    {
        // Normalize (lowercase) the institutions for the test on unique entries below.
        $institutionsLowerCased = array_map('strtolower', $institutions);
        if ($institutionsLowerCased !== array_unique($institutionsLowerCased)) {
            throw new InvalidArgumentException('Duplicate entries are not allowed in the InstitutionSet');
        }

        $this->institutions = $this->sort($institutions);
    }

    /**
     * @param Institution[]
     * @return InstitutionSet
     */
    public static function create(array $institutions)
    {
        // Verify only institutions are collected in the set
        array_walk(
            $institutions,
            function ($institution, $key) use ($institutions) {
                if (!$institution instanceof Institution) {
                    throw InvalidArgumentException::invalidType(
                        Institution::class,
                        'institutions',
                        $institutions[$key]
                    );
                }
            }
        );

        return new self($institutions);
    }

    public function equals(InstitutionSet $other)
    {
        return $this->toScalarArray() === $other->toScalarArray();
    }

    /**
     * @param Institution $institution
     * @return bool
     */
    public function isOption(Institution $institution)
    {
        return in_array($institution->getInstitution(), $this->institutions);
    }

    /**
     * @return Institution[]
     */
    public function getInstitutions()
    {
        return $this->institutions;
    }

    public function toScalarArray()
    {
        return array_map('strval', $this->institutions);
    }

    public function jsonSerialize()
    {
        return $this->institutions;
    }

    /**
     * @param Institution[] $institutions
     * @return Institution[]
     */
    private function sort(array $institutions)
    {
        usort($institutions, function (Institution $a, Institution $b) {
            return strcmp($a->getInstitution(), $b->getInstitution());
        });

        return $institutions;
    }
}

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

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionSet implements JsonSerializable, IteratorAggregate
{
    /**
     * @var array
     */
    private $institutionSet;

    /**
     * @param Institution[]
     */
    public function __construct(array $institutions)
    {
        // Verify only Institution value objects are collected in the set
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

        // Normalize (lowercase) the institutions for the test on unique entries below.
        $institutionsLowerCased = array_map('strtolower', $institutions);
        if ($institutionsLowerCased !== array_unique($institutionsLowerCased)) {
            throw new InvalidArgumentException('Duplicate entries are not allowed in the InstitutionSet');
        }

        $this->institutionSet = $institutions;
    }

    public static function fromInstitutionConfig(array $institutions)
    {
        if (empty($institutions)) {
            return new self($institutions);
        }

        $set = [];
        foreach ($institutions as $institutionTitle) {
            $set[] = new Institution($institutionTitle);
        }

        return new self($set);
    }
    
    public function equals(InstitutionSet $other)
    {
        // Compare the institution values of the sets
        $currentValues = $this->toScalarArray();
        $otherValues = $other->toScalarArray();
        return $currentValues === $otherValues;
    }

    /**
     * Return an array of institution values represented by their string value
     */
    public function toScalarArray()
    {
        return array_map('strval', $this->toArray());
    }

    private function toArray()
    {
        return $this->getIterator()->getArrayCopy();
    }

    public function jsonSerialize()
    {
        return $this->institutionSet;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->institutionSet);
    }
}

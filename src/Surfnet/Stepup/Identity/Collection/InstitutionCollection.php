<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\Stepup\Identity\Collection;

use ArrayIterator;
use Broadway\Serializer\SerializableInterface;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\Stepup\Exception\RuntimeException;
use Surfnet\Stepup\Identity\Value\Institution;

final class InstitutionCollection implements IteratorAggregate, JsonSerializable, SerializableInterface
{
    private $elements = [];

    public function __construct(array $institutions = [])
    {
        foreach ($institutions as $institution) {
            $this->add($institution);
        }
    }

    public function contains(Institution $institution)
    {
        return in_array($institution, $this->elements);
    }

    /**
     * Adds the institution to this collection
     *
     * @param Institution $institution
     * @throws RuntimeException when the institution already exists in this collection
     */
    public function add(Institution $institution)
    {
        if (in_array($institution, $this->elements)) {
            throw new RuntimeException(sprintf(
                'Institution "%s" is already in this collection',
                $institution
            ));
        }

        $this->elements[] = $institution;
    }

    /**
     * Adds all institutions from the given collection to this collection
     *
     * @param InstitutionCollection $institutionCollection
     */
    public function addAllFrom(InstitutionCollection $institutionCollection)
    {
        foreach ($institutionCollection as $institution) {
            $this->add($institution);
        }
    }

    /**
     * Removes an institution from this collection
     *
     * @param Institution $institution
     * @throws RuntimeException when the institution to remove is not in this collection
     */
    public function remove(Institution $institution)
    {
        if (!in_array($institution, $this->elements)) {
            throw new RuntimeException(sprintf(
                'Cannot remove Institution "%s" from the collection as it is not in the collection',
                $institution
            ));
        }

        $elements = array_filter($this->elements, function($inst) use ($institution) {
            return !$institution->equals($inst);
        });
        $this->elements = $elements;
    }

    /**
     * Removes all Institutions in the given collection from this collection
     *
     * @param InstitutionCollection $institutionCollection
     */
    public function removeAllIn(InstitutionCollection $institutionCollection)
    {
        foreach ($institutionCollection as $institution) {
            $this->remove($institution);
        }
    }

    public function jsonSerialize()
    {
        return ['institutions' => $this->elements];
    }

    public static function deserialize(array $data)
    {
        $institutions = array_map(function ($institution) {
            return new Institution($institution);
        }, $data);

        return new self($institutions);
    }

    public function serialize()
    {
        return array_map(function (Institution $institution) {
            return (string) $institution;
        }, $this->elements);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }
}

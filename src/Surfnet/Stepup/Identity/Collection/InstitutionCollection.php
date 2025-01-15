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
use Broadway\Serializer\Serializable as SerializableInterface;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\Stepup\Exception\RuntimeException;
use Surfnet\Stepup\Identity\Value\Institution;

/**
 * @implements IteratorAggregate<Institution>
 */
final class InstitutionCollection implements IteratorAggregate, JsonSerializable, SerializableInterface
{
    private array $elements = [];

    public function __construct(array $institutions = [])
    {
        foreach ($institutions as $institution) {
            $this->add($institution);
        }
    }

    public function contains(Institution $institution): bool
    {
        return in_array($institution, $this->elements);
    }

    /**
     * Adds the institution to this collection
     *
     * @throws RuntimeException when the institution already exists in this collection
     */
    public function add(Institution $institution): void
    {
        if (in_array($institution, $this->elements)) {
            throw new RuntimeException(
                sprintf(
                    'Institution "%s" is already in this collection',
                    $institution,
                ),
            );
        }

        $this->elements[] = $institution;
    }

    /**
     * Adds all institutions from the given collection to this collection
     */
    public function addAllFrom(InstitutionCollection $institutionCollection): void
    {
        foreach ($institutionCollection as $institution) {
            $this->add($institution);
        }
    }

    /**
     * Removes an institution from this collection
     *
     * @throws RuntimeException when the institution to remove is not in this collection
     */
    public function remove(Institution $institution): void
    {
        if (!in_array($institution, $this->elements)) {
            throw new RuntimeException(
                sprintf(
                    'Cannot remove Institution "%s" from the collection as it is not in the collection',
                    $institution,
                ),
            );
        }

        $elements = array_filter($this->elements, fn($inst): bool => !$institution->equals($inst));
        $this->elements = $elements;
    }

    /**
     * Removes all Institutions in the given collection from this collection
     */
    public function removeAllIn(InstitutionCollection $institutionCollection): void
    {
        foreach ($institutionCollection as $institution) {
            $this->remove($institution);
        }
    }

    public function jsonSerialize(): array
    {
        return ['institutions' => $this->elements];
    }

    public static function deserialize(array $data): self
    {
        $institutions = array_map(fn($institution): Institution => new Institution($institution), $data);

        return new self($institutions);
    }

    public function serialize(): array
    {
        return array_map(fn(Institution $institution): string => (string)$institution, $this->elements);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->elements);
    }
}

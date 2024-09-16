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

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Surfnet\Stepup\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Exception\LogicException;

/**
 * @implements IteratorAggregate<RaLocation>
 */
final class RaLocationList implements IteratorAggregate
{
    /**
     * @var RaLocation[]
     */
    private array $raLocations = [];

    public function __construct(array $raLocations)
    {
        foreach ($raLocations as $raLocation) {
            $this->add($raLocation);
        }
    }

    public function containsWithId(RaLocationId $raLocationId): bool
    {
        foreach ($this->raLocations as $raLocation) {
            if ($raLocation->hasId($raLocationId)) {
                return true;
            }
        }

        return false;
    }

    public function add(RaLocation $raLocation): void
    {
        if ($this->containsWithId($raLocation->getId())) {
            throw new LogicException(
                sprintf(
                    'Cannot add RaLocation with id "%s" to RaLocationList: it is already present',
                    $raLocation->getId(),
                ),
            );
        }

        $this->raLocations[] = $raLocation;
    }

    public function removeWithId(RaLocationId $raLocationId): void
    {
        foreach ($this->raLocations as $key => $raLocation) {
            if ($raLocation->hasId($raLocationId)) {
                unset($this->raLocations[$key]);
                $this->raLocations = array_values($this->raLocations);

                return;
            }
        }

        throw new LogicException(
            sprintf(
                'Cannot remove RaLocation with id "%s" from RaLocationList: it is not present',
                $raLocationId,
            ),
        );
    }

    public function getById(RaLocationId $raLocationId): RaLocation
    {
        foreach ($this->raLocations as $raLocation) {
            if ($raLocation->hasId($raLocationId)) {
                return $raLocation;
            }
        }

        throw new LogicException(
            sprintf(
                'Cannot get RaLocation by id "%s" from RaLocationList: RaLocationId not found',
                $raLocationId,
            ),
        );
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->raLocations);
    }
}

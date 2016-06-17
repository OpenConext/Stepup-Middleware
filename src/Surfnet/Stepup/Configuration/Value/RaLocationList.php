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
use IteratorAggregate;
use Surfnet\Stepup\Exception\LogicException;
use Surfnet\Stepup\Configuration\Entity\RaLocation;

final class RaLocationList implements IteratorAggregate
{
    /**
     * @var RaLocation[]
     */
    private $raLocations = [];

    public function __construct(array $raLocations)
    {
        foreach ($raLocations as $raLocation) {
            $this->add($raLocation);
        }
    }

    /**
     * @param RaLocationId $raLocationId
     * @return bool
     */
    public function containsWithId(RaLocationId $raLocationId)
    {
        foreach ($this->raLocations as $raLocation) {
            if ($raLocation->hasRaLocationId($raLocationId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param RaLocation $raLocation
     */
    public function add(RaLocation $raLocation)
    {
        if ($this->containsWithId($raLocation->getRaLocationId())) {
            throw new LogicException(sprintf(
                'Cannot add RaLocation with id "%s" to RaLocationList: it is already present',
                $raLocation->getRaLocationId()
            ));
        }

        $this->raLocations[] = $raLocation;
    }

    /**
     * @param RaLocationId $raLocationId
     */
    public function removeWithId(RaLocationId $raLocationId)
    {
        foreach ($this->raLocations as $key => $raLocation) {
            if ($raLocation->hasRaLocationId($raLocationId)) {
                unset($this->raLocations[$key]);
                $this->raLocations = array_values($this->raLocations);

                return;
            }
        }

        throw new LogicException(sprintf(
            'Cannot remove RaLocation with id "%s" from RaLocationList: it is not present',
            $raLocationId
        ));
    }

    /**
     * @param RaLocationId $raLocationId
     * @return RaLocation
     */
    public function getById(RaLocationId $raLocationId)
    {
        foreach ($this->raLocations as $raLocation) {
            if ($raLocation->hasRaLocationId($raLocationId)) {
                return $raLocation;
            }
        }

        throw new LogicException(sprintf(
            'Cannot get RaLocation by id "%s" from RaLocationList: RaLocationId not found',
            $raLocationId
        ));
    }

    public function getIterator()
    {
        return new ArrayIterator($this->raLocations);
    }
}

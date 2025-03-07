<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing;

use ArrayIterator;
use Broadway\EventHandling\EventListener as ProjectorInterface;
use Iterator;
use IteratorAggregate;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

/**
 * @implements IteratorAggregate<ProjectorInterface>
 */
final class ProjectorCollection implements IteratorAggregate
{
    /**
     * @var ProjectorInterface[]
     */
    private array $projectors = [];

    public function add(ProjectorInterface $projector): void
    {
        $this->projectors[$projector::class] = $projector;
    }

    /**
     * @return string[]
     */
    public function getProjectorNames(): array
    {
        return array_map(
            fn(ProjectorInterface $projector): string => $projector::class,
            array_values($this->projectors),
        );
    }

    /**
     * @return ProjectorCollection
     */
    public function selectByNames(array $projectorNames): ProjectorCollection
    {
        $subsetCollection = new ProjectorCollection;

        foreach ($projectorNames as $projectorName) {
            if (!array_key_exists($projectorName, $this->projectors)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot select a subset of projectors, because projector "%s" is not present in the collection',
                        $projectorName,
                    ),
                );
            }

            $subsetCollection->add($this->projectors[$projectorName]);
        }

        return $subsetCollection;
    }

    /**
     * @return bool
     */
    public function contains(ProjectorInterface $projector): bool
    {
        return array_key_exists($projector::class, $this->projectors);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->projectors);
    }
}

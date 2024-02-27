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
use IteratorAggregate;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

final class ProjectorCollection implements IteratorAggregate
{
    /**
     * @var ProjectorInterface[]
     */
    private array $projectors = [];

    /**
     * @param ProjectorInterface $projector
     */
    public function add(ProjectorInterface $projector): void
    {
        $this->projectors[get_class($projector)] = $projector;
    }

    /**
     * @return string[]
     */
    public function getProjectorNames(): array
    {
        return array_map(
            function (ProjectorInterface $projector): string {
                return get_class($projector);
            },
            array_values($this->projectors)
        );
    }

    /**
     * @param array $projectorNames
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
                        $projectorName
                    )
                );
            }

            $subsetCollection->add($this->projectors[$projectorName]);
        }

        return $subsetCollection;
    }

    /**
     * @param ProjectorInterface $projector
     * @return bool
     */
    public function contains(ProjectorInterface $projector): bool
    {
        return array_key_exists(get_class($projector), $this->projectors);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->projectors);
    }
}

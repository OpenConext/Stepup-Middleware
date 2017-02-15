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
use Broadway\ReadModel\ProjectorInterface;
use IteratorAggregate;

final class ProjectorCollection implements IteratorAggregate
{
    /**
     * @var ProjectorInterface[]
     */
    private $projectors = [];

    public function add(ProjectorInterface $projector)
    {
        $this->projectors[] = $projector;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->projectors);
    }
}

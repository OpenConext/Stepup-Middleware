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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\EventSourcing;

use Broadway\ReadModel\ProjectorInterface;
use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;

class ProjectorCollectionTest extends TestCase
{
    /**
     * @test
     * @group event-replay
     */
    public function projectors_can_be_added_to_a_projector_collection_during_runtime()
    {
        $projectorA = m::mock(ProjectorInterface::class);
        $projectorB = m::mock(ProjectorInterface::class);

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($projectorA);
        $projectorCollection->add($projectorB);

        $this->assertSame(
            [$projectorA, $projectorB],
            iterator_to_array($projectorCollection)
        );
    }
}

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

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\SraaProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\WhitelistProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\WhitelistEntryRepository;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

class ProjectorCollectionTest extends TestCase
{
    /**
     * @test
     * @group event-replay
     */
    public function projectors_can_be_added_to_a_projector_collection_during_runtime()
    {
        $sraaProjector      = new SraaProjector(m::mock(SraaRepository::class));
        $whitelistProjector = new WhitelistProjector(m::mock(WhitelistEntryRepository::class));

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($sraaProjector);
        $projectorCollection->add($whitelistProjector);

        $this->assertTrue(
            $projectorCollection->contains($sraaProjector),
            'ProjectorCollection should have contained added SraaProjector but it did not'
        );
        $this->assertTrue(
            $projectorCollection->contains($whitelistProjector),
            'ProjectorCollection should have contained added WhitelistProjector but it did not'
        );
    }
}

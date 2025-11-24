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
use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\SraaProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\WhitelistProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\WhitelistEntryRepository;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

class ProjectorCollectionTest extends TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;
    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('event-replay')]
    public function projectors_can_be_added_to_a_projector_collection_during_runtime(): void
    {
        $sraaProjector = new SraaProjector(m::mock(SraaRepository::class));
        $whitelistProjector = new WhitelistProjector(m::mock(WhitelistEntryRepository::class));

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($sraaProjector);
        $projectorCollection->add($whitelistProjector);

        $this->assertTrue(
            $projectorCollection->contains($sraaProjector),
            'ProjectorCollection should have contained added SraaProjector but it did not',
        );
        $this->assertTrue(
            $projectorCollection->contains($whitelistProjector),
            'ProjectorCollection should have contained added WhitelistProjector but it did not',
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('event-replay')]
    public function projector_names_can_be_retrieved_from_a_projector_collection(): void
    {
        $sraaProjector = new SraaProjector(m::mock(SraaRepository::class));
        $whitelistProjector = new WhitelistProjector(m::mock(WhitelistEntryRepository::class));

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($sraaProjector);
        $projectorCollection->add($whitelistProjector);

        $expectedProjectorNames = [SraaProjector::class, WhitelistProjector::class];
        $actualProjectorNames = $projectorCollection->getProjectorNames();

        $this->assertSame(
            $expectedProjectorNames,
            $actualProjectorNames,
            'Projector names cannot be retrieved correctly from a ProjectorCollection',
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('event-replay')]
    public function a_subset_of_projectors_can_be_selected_from_a_projector_collection(): void
    {
        $sraaProjector = new SraaProjector(m::mock(SraaRepository::class));
        $whitelistProjector = new WhitelistProjector(m::mock(WhitelistEntryRepository::class));

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($sraaProjector);
        $projectorCollection->add($whitelistProjector);

        $projectorSelection = $projectorCollection->selectByNames([$sraaProjector::class]);

        $this->assertTrue(
            $projectorSelection->contains($sraaProjector),
            'Subset of ProjectorCollection should contain SraaProjector but it did not',
        );
        $this->assertFalse(
            $projectorSelection->contains($whitelistProjector),
            'Subset of ProjectorCollection should contain WhitelistProjector but it did not',
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('event-replay')]
    public function a_subset_containing_projectors_not_present_in_a_projector_collection_cannot_be_selected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not present in the collection');

        $sraaProjector = new SraaProjector(m::mock(SraaRepository::class));
        $nonPresentWhitelistProjector = new WhitelistProjector(m::mock(WhitelistEntryRepository::class));

        $projectorCollection = new ProjectorCollection;
        $projectorCollection->add($sraaProjector);

        $projectorCollection->selectByNames([$nonPresentWhitelistProjector::class]);
    }
}

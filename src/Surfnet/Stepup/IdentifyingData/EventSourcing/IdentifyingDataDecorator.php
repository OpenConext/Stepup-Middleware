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

namespace Surfnet\Stepup\IdentifyingData\EventSourcing;

use Broadway\Domain\AggregateRoot;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\Repository\RepositoryInterface;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\IdentifyingData\Api\IdentifyingDataHolder;
use Surfnet\Stepup\IdentifyingData\Entity\IdentifyingDataRepository;

class IdentifyingDataDecorator implements RepositoryInterface
{
    private $aggregateRootRepository;

    private $identifyingDataRepository;

    public function __construct(
        EventSourcingRepository $aggregateRootRepository,
        IdentifyingDataRepository $identifyingDataRepository
    ) {
        $this->aggregateRootRepository = $aggregateRootRepository;
        $this->identifyingDataRepository = $identifyingDataRepository;
    }

    public function add(AggregateRoot $aggregate)
    {
        $this->assertImplementsAccessibleIdentifyingDataInterface($aggregate);

//        $identifyingData = $aggregate->
        return $this->aggregateRootRepository->add($aggregate);
    }

    public function load($id)
    {
        $aggregate = $this->aggregateRootRepository->load($id);

        $this->assertImplementsAccessibleIdentifyingDataInterface($aggregate);

        return $aggregate;
    }

    private function assertImplementsAccessibleIdentifyingDataInterface($aggregate)
    {
        if (!$aggregate instanceof IdentifyingDataHolder) {
            throw new DomainException(
                'The AggregateRoot must implement the \Surfnet\Stepup\Identity\Api\AccessibleIdentifyingData interface '
                . 'to be able to set/get the Identifying Data'
            );
        }
    }
}

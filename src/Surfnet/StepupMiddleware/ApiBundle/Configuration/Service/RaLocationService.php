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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Service;

use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Query\RaLocationQuery;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\RaLocationRepository;

final class RaLocationService
{
    /**
     * @var RaLocationRepository
     */
    private $repository;

    public function __construct(RaLocationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param RaLocationQuery $query
     * @return null|RaLocation[]
     */
    public function search(RaLocationQuery $query)
    {
        return $this->repository->search($query);
    }

    /**
     * @param RaLocationId $raLocationId
     * @return RaLocation[]
     */
    public function findByRaLocationId(RaLocationId $raLocationId)
    {
        return $this->repository->findByRaLocationId($raLocationId);
    }
}

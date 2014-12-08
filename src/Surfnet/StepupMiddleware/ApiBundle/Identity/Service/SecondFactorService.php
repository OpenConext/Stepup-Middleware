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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchVerifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;

class SecondFactorService extends AbstractSearchService
{
    /**
     * @var UnverifiedSecondFactorRepository
     */
    private $unverifiedRepository;

    /**
     * @var VerifiedSecondFactorRepository
     */
    private $verifiedRepository;

    /**
     * @param UnverifiedSecondFactorRepository $unverifiedRepository
     * @param VerifiedSecondFactorRepository   $verifiedRepository
     */
    public function __construct(
        UnverifiedSecondFactorRepository $unverifiedRepository,
        VerifiedSecondFactorRepository $verifiedRepository
    ) {
        $this->unverifiedRepository = $unverifiedRepository;
        $this->verifiedRepository = $verifiedRepository;
    }

    /**
     * @param SearchUnverifiedSecondFactorCommand $command
     * @return \Pagerfanta\PagerfantaInterface
     */
    public function searchUnverifiedSecondFactors(SearchUnverifiedSecondFactorCommand $command)
    {
        $query = $this->unverifiedRepository->createSearchQuery($command);

        $paginator = $this->createPaginatorFrom($query, $command);

        return $paginator;
    }

    /**
     * @param SearchVerifiedSecondFactorCommand $command
     * @return \Pagerfanta\PagerfantaInterface
     */
    public function searchVerifiedSecondFactors(SearchVerifiedSecondFactorCommand $command)
    {
        $query = $this->verifiedRepository->createSearchQuery($command);

        $paginator = $this->createPaginatorFrom($query, $command);

        return $paginator;
    }
}

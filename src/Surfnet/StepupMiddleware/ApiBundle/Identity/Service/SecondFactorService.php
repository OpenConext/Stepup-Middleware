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

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchVerifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchVettedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;

class SecondFactorService
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
     * @var VettedSecondFactorRepository
     */
    private $vettedRepository;

    /**
     * @param UnverifiedSecondFactorRepository $unverifiedRepository
     * @param VerifiedSecondFactorRepository $verifiedRepository
     * @param VettedSecondFactorRepository $vettedRepository
     */
    public function __construct(
        UnverifiedSecondFactorRepository $unverifiedRepository,
        VerifiedSecondFactorRepository $verifiedRepository,
        VettedSecondFactorRepository $vettedRepository
    ) {
        $this->unverifiedRepository = $unverifiedRepository;
        $this->verifiedRepository = $verifiedRepository;
        $this->vettedRepository = $vettedRepository;
    }

    /**
     * @param SearchUnverifiedSecondFactorCommand $command
     * @return Pagerfanta
     */
    public function searchUnverifiedSecondFactors(SearchUnverifiedSecondFactorCommand $command)
    {
        $query = $this->unverifiedRepository->createSearchQuery($command);

        $adapter  = new DoctrineORMAdapter($query);
        $paginator = new Pagerfanta($adapter);
        $paginator->setMaxPerPage($command->itemsPerPage);
        $paginator->setCurrentPage($command->pageNumber);

        return $paginator;
    }

    /**
     * @param SearchVerifiedSecondFactorCommand $command
     * @return Pagerfanta
     */
    public function searchVerifiedSecondFactors(SearchVerifiedSecondFactorCommand $command)
    {
        $query = $this->verifiedRepository->createSearchQuery($command);

        $adapter  = new DoctrineORMAdapter($query);
        $paginator = new Pagerfanta($adapter);
        $paginator->setMaxPerPage($command->itemsPerPage);
        $paginator->setCurrentPage($command->pageNumber);

        return $paginator;
    }

    /**
     * @param SearchVettedSecondFactorCommand $command
     * @return Pagerfanta
     */
    public function searchVettedSecondFactors(SearchVettedSecondFactorCommand $command)
    {
        $query = $this->vettedRepository->createSearchQuery($command);

        $adapter  = new DoctrineORMAdapter($query);
        $paginator = new Pagerfanta($adapter);
        $paginator->setMaxPerPage($command->itemsPerPage);
        $paginator->setCurrentPage($command->pageNumber);

        return $paginator;
    }
}

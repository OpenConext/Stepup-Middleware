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

use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\UnverifiedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorOfIdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VettedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;

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
     * @param UnverifiedSecondFactorQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function searchUnverifiedSecondFactors(UnverifiedSecondFactorQuery $query)
    {
        $doctrineQuery = $this->unverifiedRepository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }

    /**
     * @param VerifiedSecondFactorQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function searchVerifiedSecondFactors(VerifiedSecondFactorQuery $query)
    {
        $doctrineQuery = $this->verifiedRepository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }


    /**
     * @param VerifiedSecondFactorOfIdentityQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function searchVerifiedSecondFactorsOfIdentity(VerifiedSecondFactorOfIdentityQuery $query)
    {
        $doctrineQuery = $this->verifiedRepository->createSearchForIdentityQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }

    /**
     * @param VettedSecondFactorQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function searchVettedSecondFactors(VettedSecondFactorQuery $query)
    {
        $doctrineQuery = $this->vettedRepository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }

    /**
     * @param SecondFactorId $id
     * @return null|UnverifiedSecondFactor
     */
    public function findUnverified(SecondFactorId $id)
    {
        return $this->unverifiedRepository->find($id);
    }


    /**
     * @param SecondFactorId $id
     * @return null|VerifiedSecondFactor
     */
    public function findVerified(SecondFactorId $id)
    {
        return $this->verifiedRepository->find($id);
    }


    /**
     * @param SecondFactorId $id
     * @return null|VettedSecondFactor
     */
    public function findVetted(SecondFactorId $id)
    {
        return $this->vettedRepository->find($id);
    }
}

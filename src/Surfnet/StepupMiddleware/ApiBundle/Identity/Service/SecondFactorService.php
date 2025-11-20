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

use Pagerfanta\Pagerfanta;
use Surfnet\Stepup\Identity\Value\IdentityId;
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

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") Coupling to Entities and ValueObjects in parameters cause the high
 * coupling warning. Decoupling them (replacing VOs with primitives) bring a degradation in type strictness.
 *
 * @extends AbstractSearchService<UnverifiedSecondFactor|UnverifiedSecondFactor|VettedSecondFactor>
 */
class SecondFactorService extends AbstractSearchService
{
    public function __construct(
        private readonly UnverifiedSecondFactorRepository $unverifiedRepository,
        private readonly VerifiedSecondFactorRepository $verifiedRepository,
        private readonly VettedSecondFactorRepository $vettedRepository,
    ) {
    }

    /**
     * @return Pagerfanta<UnverifiedSecondFactor>
     */
    public function searchUnverifiedSecondFactors(UnverifiedSecondFactorQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->unverifiedRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    /**
     * @return Pagerfanta<VerifiedSecondFactor>
     */
    public function searchVerifiedSecondFactors(VerifiedSecondFactorQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->verifiedRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }


    /**
     * @return Pagerfanta<VerifiedSecondFactor>
     */
    public function searchVerifiedSecondFactorsOfIdentity(VerifiedSecondFactorOfIdentityQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->verifiedRepository->createSearchForIdentityQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    /**
     * @return Pagerfanta<VettedSecondFactor>
     */
    public function searchVettedSecondFactors(VettedSecondFactorQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->vettedRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    public function findUnverified(SecondFactorId $id): ?UnverifiedSecondFactor
    {
        return $this->unverifiedRepository->find($id);
    }

    public function findVerified(SecondFactorId $id): ?VerifiedSecondFactor
    {
        return $this->verifiedRepository->find($id);
    }

    public function findVetted(SecondFactorId $id): ?VettedSecondFactor
    {
        return $this->vettedRepository->find($id);
    }

    public function hasVettedByIdentity(IdentityId $id): bool
    {
        $vettedSecondFactors = $this->vettedRepository->findBy(['identityId' => (string)$id]);
        return $vettedSecondFactors !== [];
    }
}

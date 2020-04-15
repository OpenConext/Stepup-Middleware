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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query\Expr\Join;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaCandidateRepository extends EntityRepository
{
    /**
     * @var InstitutionAuthorizationRepositoryFilter
     */
    private $authorizationRepositoryFilter;

    public function __construct(
        EntityManager $em,
        Mapping\ClassMetadata $class,
        InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
    ) {
        parent::__construct($em, $class);
        $this->authorizationRepositoryFilter = $authorizationRepositoryFilter;
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(RaCandidateQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rac');

        // Modify query to filter on authorization:
        // For the RA candidates we want the identities that we could make RA. Because we then need to look at the
        // select_raa's we have to look at the institution of the candidate because that's the institution we could
        // select RA's from. Hence the 'rac.institution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'rac.institution',
            'iac'
        );

        if ($query->institution) {
            $queryBuilder
                ->andWhere('rac.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('rac.commonName LIKE :commonName')
                ->setParameter('commonName', sprintf('%%%s%%', $query->commonName));
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('rac.email LIKE :email')
                ->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if (!empty($query->secondFactorTypes)) {
            $queryBuilder
                ->innerJoin(VettedSecondFactor::class, 'vsf', Join::WITH, 'rac.identityId = vsf.identityId')
                ->andWhere('vsf.type IN (:secondFactorTypes)')
                ->setParameter('secondFactorTypes', $query->secondFactorTypes);
        }

        if (!empty($query->raInstitution)) {
            $queryBuilder
                ->andWhere('rac.raInstitution = :raInstitution')
                ->setParameter('raInstitution', $query->raInstitution);
        }

        $queryBuilder->groupBy('rac.identityId');

        return $queryBuilder->getQuery();
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createOptionsQuery(RaCandidateQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rac')
            ->select('rac.institution');

        // Modify query to filter on authorization:
        // For the RA candidates we want the identities that we could make RA. Because we then need to look at the
        // select_raa's we have to look at the institution of the candidate because that's the institution we could
        // select RA's from. Hence the 'rac.institution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'rac.institution',
            'iac'
        );

        return $queryBuilder->getQuery();
    }

    /**
     * @param string $identityId
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @return RaCandidate[]
     */
    public function findAllRaasByIdentityId($identityId, InstitutionAuthorizationContextInterface $authorizationContext)
    {
        $queryBuilder = $this->createQueryBuilder('rac')
            ->where('rac.identityId = :identityId')
            ->setParameter('identityId', $identityId)
            ->orderBy('rac.raInstitution');

        // Modify query to filter on authorization:
        // For the RA candidates we want the identities that we could make RA. Because we then need to look at the
        // select_raa's we have to look at the institution of the candidate because that's the institution we could
        // select RA's from. Hence the 'rac.institution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $authorizationContext,
            'rac.institution',
            'iac'
        );

        return $queryBuilder->getQuery()->getResult();
    }
}

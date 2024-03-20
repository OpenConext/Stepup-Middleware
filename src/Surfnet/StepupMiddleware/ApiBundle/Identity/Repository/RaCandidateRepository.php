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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaCandidateRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter,
    ) {
        parent::__construct($registry, RaCandidate::class);
    }

    /**
     * @return Query
     */
    public function createSearchQuery(RaCandidateQuery $query): Query
    {
        $queryBuilder = $this->getBaseQuery();

        // Modify query to filter on authorization:
        // For the RA candidates we want the identities that we could make RA. Because we then need to look at the
        // select_raa's we have to look at the institution of the candidate because that's the institution we could
        // select RA's from. Hence the 'rac.institution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'i.institution',
            'iac',
        );

        if ($query->institution) {
            $queryBuilder
                ->andWhere('i.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('i.commonName LIKE :commonName')
                ->setParameter('commonName', sprintf('%%%s%%', $query->commonName));
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('i.email LIKE :email')
                ->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if (!empty($query->secondFactorTypes)) {
            $queryBuilder
                ->andWhere('vsf.type IN (:secondFactorTypes)')
                ->setParameter('secondFactorTypes', $query->secondFactorTypes);
            // Self asserted tokens diminish the LoA never resulting in a valid
            // second factor candidate. Only on-premise and self-vetted tokens
            // can be a valid option.
            $queryBuilder
                ->andWhere('vsf.vettingType != :vettingType')
                ->setParameter('vettingType', VettingType::TYPE_SELF_ASSERTED_REGISTRATION);
        }

        if (!empty($query->raInstitution)) {
            $queryBuilder
                ->andWhere('a.raInstitution = :raInstitution')
                ->setParameter('raInstitution', $query->raInstitution);
        }

        $queryBuilder->groupBy('i.id');

        return $queryBuilder->getQuery();
    }

    /**
     * @return Query
     */
    public function createOptionsQuery(RaCandidateQuery $query): Query
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('a.institution')
            ->from(InstitutionAuthorization::class, 'a')
            ->where("a.institutionRole = 'select_raa'");

        // Modify query to filter on authorization:
        // For the RA candidates we want the identities that we could make RA. Because we then need to look at the
        // select_raa's we have to look at the institution of the candidate because that's the institution we could
        // select RA's from. Hence the 'rac.institution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'a.institution',
            'iac',
        );

        return $queryBuilder->getQuery();
    }

    /**
     * @return array|null
     */
    public function findOneByIdentityId(string $identityId): ?array
    {
        // Finds a single identity by its identity id. Returns the identity as an array
        $queryBuilder = $this->getBaseQuery()
            ->andWhere('i.id = :identityId')
            ->setParameter('identityId', $identityId)
            ->groupBy('i.id')
            ->orderBy('a.institution');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return QueryBuilder
     */
    private function getBaseQuery(): QueryBuilder
    {
        // Base query to get all allowed ra candidates
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'i.id as identity_id, i.institution, i.commonName as common_name, i.email, i.nameId AS name_id, a.institution AS ra_institution',
            )
            ->from(VettedSecondFactor::class, 'vsf')
            ->innerJoin(Identity::class, 'i', Join::WITH, "vsf.identityId = i.id")
            ->innerJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "a.institutionRole = 'select_raa' AND a.institutionRelation = i.institution",
            );

        // Filter out candidates who are already ra
        // Todo: filter out SRAA's ?
        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from(RaListing::class, "l")
            ->where("l.identityId = i.id AND l.raInstitution = a.institution");

        $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQuery->getDQL())));

        return $queryBuilder;
    }
}

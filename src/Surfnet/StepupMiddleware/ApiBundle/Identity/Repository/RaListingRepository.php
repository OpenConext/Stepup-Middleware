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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaListingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter,
    ) {
        parent::__construct($registry, RaListing::class);
    }

    /**
     * @param IdentityId $identityId The RA's identity id.
     * @return null|RaListing[]
     */
    public function findByIdentityId(IdentityId $identityId): array
    {
        return parent::findBy(['identityId' => (string)$identityId]);
    }

    /**
     * @param IdentityId $identityId The RA's identity id.
     * @return null|RaListing
     */
    public function findByIdentityIdAndRaInstitution(IdentityId $identityId, Institution $raInstitution): ?object
    {
        return parent::findOneBy([
            'identityId' => (string)$identityId,
            'raInstitution' => (string)$raInstitution,
        ]);
    }


    /**
     * @param IdentityId $identityId The RA's identity id.
     * @return null|RaListing
     */
    public function findByIdentityIdAndRaInstitutionWithContext(
        IdentityId $identityId,
        Institution $raInstitution,
        InstitutionAuthorizationContextInterface $authorizationContext,
    ) {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.identityId = :identityId')
            ->andWhere('r.raInstitution = :raInstitution')
            ->setParameter('identityId', $identityId)
            ->setParameter('raInstitution', (string)$raInstitution)
            ->orderBy('r.raInstitution');

        // Modify query to filter on authorization:
        // For the RA listing we want identities that are already RA. Because we then need to look at the use_raa's
        // we have to look at the RA-institutions because that's the institution the user is RA for and we should use
        // those RA's. Hence the 'r.raInstitution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $authorizationContext,
            'r.raInstitution',
            'iac',
        );

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param IdentityId $identityId The RA's identity id.
     * @return RaListing[]
     */
    public function findByIdentityIdAndInstitution(IdentityId $identityId, Institution $institution): array
    {
        return parent::findBy([
            'identityId' => (string)$identityId,
            'institution' => (string)$institution,
        ]);
    }

    public function save(RaListing $raListingEntry): void
    {
        $this->getEntityManager()->persist($raListingEntry);
        $this->getEntityManager()->flush();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) The amount of if statements do not necessarily make the method
     * @SuppressWarnings(PHPMD.NPathComplexity)      below complex or hard to maintain.
     *
     * @return Query
     */
    public function createSearchQuery(RaListingQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if ($query->institution) {
            $queryBuilder
                ->andWhere('r.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->identityId) {
            $queryBuilder
                ->andWhere('r.identityId = :identityId')
                ->setParameter('identityId', (string)$query->identityId);
        }

        if ($query->name) {
            $queryBuilder
                ->andWhere('r.commonName LIKE :name')
                ->setParameter('name', sprintf('%%%s%%', $query->name));
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('r.email LIKE :email')
                ->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if ($query->role) {
            $queryBuilder
                ->andWhere('r.role = :role')
                ->setParameter('role', (string)$query->role);
        }

        if ($query->raInstitution) {
            $queryBuilder
                ->andWhere('r.raInstitution = :raInstitution')
                ->setParameter('raInstitution', (string)$query->raInstitution);
        }

        // Modify query to filter on authorization:
        // For the RA listing we want identities that are already RA. Because we then need to look at the use_raa's
        // we have to look at the RA-institutions because that's the institution the user is RA for and we should use
        // those RA's. Hence the 'r.raInstitution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'r.raInstitution',
            'iac',
        );

        if (!$query->orderBy) {
            return $queryBuilder->getQuery();
        }

        $orderDirection = $query->orderDirection === 'asc' ? 'ASC' : 'DESC';

        match ($query->orderBy) {
            'commonName' => $queryBuilder->orderBy('r.commonName', $orderDirection),
            default => throw new RuntimeException(sprintf('Unknown order by column "%s"', $query->orderBy)),
        };

        return $queryBuilder->getQuery();
    }

    /**
     * @return Query
     */
    public function createOptionsQuery(RaListingQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('r.institution, r.raInstitution')
            ->groupBy('r.institution, r.raInstitution');

        // Modify query to filter on authorization:
        // For the RA listing we want identities that are already RA. Because we then need to look at the use_raa's
        // we have to look at the RA-institutions because that's the institution the user is RA for and we should use
        // those RA's. Hence the 'r.raInstitution'.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'r.raInstitution',
            'iac',
        );

        return $queryBuilder->getQuery();
    }

    /**
     * @return ArrayCollection
     */
    public function listRasFor(Institution $raInstitution): ArrayCollection
    {
        $listings = $this->createQueryBuilder('rl')
            ->where('rl.raInstitution = :institution')
            ->setParameter('institution', $raInstitution)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($listings);
    }

    /**
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId, Institution $institution): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'ral')
            ->where('ral.identityId = :identityId')
            ->andWhere('ral.raInstitution = :institution')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->execute();
    }

    /**
     * @return void
     */
    public function removeByIdentityIdAndRaInstitution(IdentityId $identityId, Institution $raInstitution): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'ral')
            ->where('ral.identityId = :identityId')
            ->andWhere('ral.raInstitution = :institution')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->setParameter('institution', $raInstitution)
            ->getQuery()
            ->execute();
    }

    /**
     * @return void
     */
    public function removeByIdentityIdAndInstitution(IdentityId $identityId, Institution $institution): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'ral')
            ->where('ral.identityId = :identityId')
            ->andWhere('ral.institution = :institution')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->setParameter('institution', $institution)
            ->getQuery()
            ->execute();
    }
}

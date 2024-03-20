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

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorOfIdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;

class VerifiedSecondFactorRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter,
    ) {
        parent::__construct($registry, VerifiedSecondFactor::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?VerifiedSecondFactor
    {
        /** @var VerifiedSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @return VerifiedSecondFactor[]
     */
    public function findByDate(DateTime $requestedAt): array
    {
        $fromDate = clone $requestedAt;
        $fromDate->setTime(0, 0);

        $toDate = clone $requestedAt;
        $toDate->setTime(23, 59, 59);

        return $this->createQueryBuilder('sf')
            ->where('sf.registrationRequestedAt <= :toDate')
            ->andWhere('sf.registrationRequestedAt >= :fromDate')
            ->setParameter('toDate', $toDate)
            ->setParameter('fromDate', $fromDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Query
     */
    public function createSearchQuery(VerifiedSecondFactorQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        if ($query->identityId) {
            $queryBuilder
                ->andWhere('sf.identityId = :identityId')
                ->setParameter('identityId', (string)$query->identityId);
        }

        if ($query->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.id = :secondFactorId')
                ->setParameter('secondFactorId', (string)$query->secondFactorId);
        }

        if (is_string($query->registrationCode)) {
            $queryBuilder
                ->andWhere('sf.registrationCode = :registrationCode')
                ->setParameter('registrationCode', $query->registrationCode);
        }

        // Modify query to filter on authorization:
        // We want to list all second factors of the institution we are RA for.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'sf.institution',
            'iac',
        );

        return $queryBuilder->getQuery();
    }

    public function createSearchForIdentityQuery(VerifiedSecondFactorOfIdentityQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        $queryBuilder
            ->andWhere('sf.identityId = :identityId')
            ->setParameter('identityId', (string)$query->identityId);

        return $queryBuilder->getQuery();
    }

    public function removeByIdentityId(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'sf')
            ->where('sf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    public function save(VerifiedSecondFactor $secondFactor): void
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    public function remove(VerifiedSecondFactor $secondFactor): void
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

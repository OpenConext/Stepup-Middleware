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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Exception\RuntimeException;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\SecondFactorStatusType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

class RaSecondFactorRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter,
    ) {
        parent::__construct($registry, RaSecondFactor::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?RaSecondFactor
    {
        /** @var RaSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @param string $identityId
     * @return RaSecondFactor[]
     */
    public function findByIdentityId(string $identityId): array
    {
        return parent::findBy(['identityId' => $identityId]);
    }


    /**
     * @param string $institution
     * @return RaSecondFactor[]
     */
    public function findByInstitution(string $institution): array
    {
        return parent::findBy(['institution' => $institution]);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) The amount of if statements do not necessarily make the method
     *                                               below complex or hard to maintain.
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createSearchQuery(RaSecondFactorQuery $query): Query
    {
        $queryBuilder = $this
            ->createQueryBuilder('sf');

        // Modify query to filter on authorization context
        // We want to list all second factors of the institution we are RA for.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'sf.institution',
            'iac',
        );

        if ($query->name) {
            $queryBuilder->andWhere('sf.name LIKE :name')->setParameter('name', sprintf('%%%s%%', $query->name));
        }

        if ($query->type) {
            $queryBuilder->andWhere('sf.type = :type')->setParameter('type', $query->type);
        }

        if ($query->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.secondFactorId = :secondFactorId')
                ->setParameter('secondFactorId', $query->secondFactorId);
        }

        if ($query->email) {
            $queryBuilder->andWhere('sf.email LIKE :email')->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if ($query->institution) {
            $queryBuilder->andWhere('sf.institution = :institution')->setParameter('institution', $query->institution);
        }

        if ($query->status) {
            $stringStatus = $query->status;
            if (!SecondFactorStatus::isValidStatus($stringStatus)) {
                throw new RuntimeException(
                    sprintf(
                        'Received invalid status "%s" in RaSecondFactorRepository::createSearchQuery',
                        $stringStatus,
                    ),
                );
            }

            // we need to resolve the string value to database value using the correct doctrine type. Normally this is
            // done by doctrine itself, however the queries PagerFanta creates somehow manages to mangle this...
            // so we do it by hand
            $doctrineType = Type::getType(SecondFactorStatusType::NAME);
            $secondFactorStatus = SecondFactorStatus::$stringStatus();

            $databaseValue = $doctrineType->convertToDatabaseValue(
                $secondFactorStatus,
                $this->getEntityManager()->getConnection()->getDatabasePlatform(),
            );

            $queryBuilder->andWhere('sf.status = :status')->setParameter('status', $databaseValue);
        }

        match ($query->orderBy) {
            'name', 'type', 'secondFactorId', 'email', 'institution', 'status' => $queryBuilder->orderBy(
                sprintf('sf.%s', $query->orderBy),
                $query->orderDirection === 'desc' ? 'DESC' : 'ASC',
            ),
            default => $queryBuilder->getQuery(),
        };

        return $queryBuilder->getQuery();
    }

    /**
     * @return Query
     */
    public function createOptionsQuery(RaSecondFactorQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('sf')
            ->select('sf.institution')
            ->groupBy('sf.institution');

        // Modify query to filter on authorization context
        // We want to list all second factors of the institution we are RA for.
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'sf.institution',
            'iac',
        );

        return $queryBuilder->getQuery();
    }

    /**
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'rasf')
            ->where('rasf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    public function save(RaSecondFactor $secondFactor): void
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    /**
     * @param RaSecondFactor[] $secondFactors
     */
    public function saveAll(array $secondFactors): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($secondFactors as $secondFactor) {
            $entityManager->persist($secondFactor);
        }

        $entityManager->flush();
    }

    public function updateStatusByIdentityIdToForgotten(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update($this->getEntityName(), 'rasf')
            ->set('rasf.status', ":forgotten")
            ->where('rasf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->setParameter('forgotten', 40)
            ->getQuery()
            ->execute();
    }
}

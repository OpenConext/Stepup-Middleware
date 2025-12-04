<?php

/**
 * Copyright 2022 SURFnet bv
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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\RecoveryTokenStatusType;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RecoveryToken;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RecoveryTokenQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

/**
 * @extends ServiceEntityRepository<RecoveryToken>
 */
class RecoveryTokenRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter,
    ) {
        parent::__construct($registry, RecoveryToken::class);
    }

    public function save(RecoveryToken $entry): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();
    }

    public function remove(RecoveryToken $recoveryToken): void
    {
        $this->getEntityManager()->remove($recoveryToken);
        $this->getEntityManager()->flush();
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function createSearchQuery(RecoveryTokenQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        if ($query->authorizationContext instanceof InstitutionAuthorizationContextInterface) {
            // Modify query to filter on authorization context
            // We want to list all recovery tokens of the institution we are RA for.
            $this->authorizationRepositoryFilter->filter(
                $queryBuilder,
                $query->authorizationContext,
                'rt.institution',
                'iac',
            );
        }
        if ($query->identityId instanceof IdentityId) {
            $queryBuilder
                ->andWhere('rt.identityId = :identityId')
                ->setParameter('identityId', $query->identityId);
        }
        if ($query->type) {
            $queryBuilder
                ->andWhere('rt.type = :type')
                ->setParameter('type', $query->type);
        }
        if ($query->status) {
            $stringStatus = $query->status;
            if (!RecoveryTokenStatus::isValidStatus($stringStatus)) {
                throw new RuntimeException(
                    sprintf(
                        'Received invalid status "%s" in RecoveryTokenRepository::createSearchQuery',
                        $stringStatus,
                    ),
                );
            }

            // we need to resolve the string value to database value using the correct doctrine type. Normally this is
            // done by doctrine itself, however the queries PagerFanta creates somehow manages to mangle this...
            // so we do it by hand
            $doctrineType = Type::getType(RecoveryTokenStatusType::NAME);
            $secondFactorStatus = RecoveryTokenStatus::$stringStatus();

            $databaseValue = $doctrineType->convertToDatabaseValue(
                $secondFactorStatus,
                $this->getEntityManager()->getConnection()->getDatabasePlatform(),
            );

            $queryBuilder->andWhere('rt.status = :status')->setParameter('status', $databaseValue);
        }
        if ($query->name) {
            $queryBuilder
                ->andWhere('rt.name LIKE :name')
                ->setParameter('name', sprintf('%%%s%%', $query->name));
        }
        if ($query->email) {
            $queryBuilder
                ->andWhere('rt.email LIKE :email')
                ->setParameter('email', sprintf('%%%s%%', $query->email));
        }
        if ($query->institution) {
            $queryBuilder
                ->andWhere('rt.institution = :institution')
                ->setParameter('institution', $query->institution);
        }
        match ($query->orderBy) {
            'name', 'type', 'email', 'institution', 'status' => $queryBuilder->orderBy(
                sprintf('rt.%s', $query->orderBy),
                $query->orderDirection === 'desc' ? 'DESC' : 'ASC',
            ),
            default => $queryBuilder->getQuery(),
        };

        return $queryBuilder->getQuery();
    }

    public function createOptionsQuery(RecoveryTokenQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('sf')
            ->select('sf.institution')
            ->groupBy('sf.institution');

        if ($query->authorizationContext instanceof InstitutionAuthorizationContextInterface) {
            // Modify query to filter on authorization context
            // We want to list all second factors of the institution we are RA for.
            $this->authorizationRepositoryFilter->filter(
                $queryBuilder,
                $query->authorizationContext,
                'sf.institution',
                'iac',
            );
        }
        return $queryBuilder->getQuery();
    }

    public function removeByIdentity(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'rt')
            ->where('rt.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }
}

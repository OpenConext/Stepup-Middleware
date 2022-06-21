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
use Doctrine\Common\Persistence\ManagerRegistry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RecoveryToken;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RecoveryTokenQuery;

class RecoveryTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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

    public function createSearchQuery(RecoveryTokenQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        if ($query->identityId) {
            $queryBuilder
                ->andWhere('rt.identityId = :identityId')
                ->setParameter('identityId', $query->identityId);
        }
        if ($query->type) {
            $queryBuilder
                ->andWhere('rt.type = :type')
                ->setParameter('type', $query->type);
        }

        return $queryBuilder->getQuery();
    }
}

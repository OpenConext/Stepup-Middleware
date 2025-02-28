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
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VettedSecondFactorQuery;

/**
 * @extends ServiceEntityRepository<VettedSecondFactor>
 */
class VettedSecondFactorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VettedSecondFactor::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?VettedSecondFactor
    {
        /** @var VettedSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    public function createSearchQuery(VettedSecondFactorQuery $query): Query
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        if ($query->identityId instanceof \Surfnet\Stepup\Identity\Value\IdentityId) {
            $queryBuilder
                ->andWhere('sf.identityId = :identityId')
                ->setParameter('identityId', (string)$query->identityId);
        }

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

    public function save(VettedSecondFactor $secondFactor): void
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    public function remove(VettedSecondFactor $secondFactor): void
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;

class VerifiedSecondFactorRepository extends EntityRepository
{
    /**
     * @param string $id
     * @return VerifiedSecondFactor|null
     */
    public function find($id)
    {
        /** @var VerifiedSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @param VerifiedSecondFactorQuery $query
     * @return Query
     */
    public function createSearchQuery(VerifiedSecondFactorQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        if ($query->identityId) {
            $queryBuilder
                ->andWhere('sf.identity = :identityId')
                ->setParameter('identityId', (string) $query->identityId);
        }

        if ($query->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.id = :secondFactorId')
                ->setParameter('secondFactorId', (string) $query->secondFactorId);
        }

        if (is_string($query->registrationCode)) {
            $queryBuilder
                ->andWhere('sf.registrationCode = :registrationCode')
                ->setParameter('registrationCode', $query->registrationCode);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param VerifiedSecondFactor $secondFactor
     */
    public function save(VerifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    public function remove(VerifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

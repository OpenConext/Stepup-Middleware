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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;

class UnverifiedSecondFactorRepository extends EntityRepository
{
    /**
     * @param string $id
     * @return UnverifiedSecondFactor|null
     */
    public function find($id)
    {
        /** @var UnverifiedSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @param SearchUnverifiedSecondFactorCommand $command
     * @return Query
     */
    public function createSearchQuery(SearchUnverifiedSecondFactorCommand $command)
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        if ($command->identityId) {
            $queryBuilder
                ->andWhere('sf.identity = :identityId')
                ->setParameter('identityId', (string) $command->identityId);
        }

        if ($command->verificationNonce) {
            $queryBuilder->andWhere('sf.verificationNonce = :verificationNonce');
            $queryBuilder->setParameter('verificationNonce', $command->verificationNonce);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param UnverifiedSecondFactor $secondFactor
     */
    public function save(UnverifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    public function remove(UnverifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

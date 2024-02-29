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

namespace Surfnet\StepupMiddleware\GatewayBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SecondFactor;

class SecondFactorRepository extends EntityRepository
{
    public $_entityName;

    public function save(SecondFactor $secondFactor): void
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    /**
     * @return SecondFactor|null
     */
    public function findOneBySecondFactorId(SecondFactorId $secondFactorId): ?object
    {
        return $this->findOneBy(['secondFactorId' => (string)$secondFactorId]);
    }

    /**
     * @return SecondFactor[]
     */
    public function findByIdentityId(IdentityId $identityId): array
    {
        return $this->findBy(['identityId' => (string)$identityId]);
    }

    public function removeByIdentityId(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'sf')
            ->where('sf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    public function remove(SecondFactor $secondFactor): void
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

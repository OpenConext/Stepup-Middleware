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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;

class RaCandidateRepository extends EntityRepository
{
    public function save(RaCandidate $raCandidate)
    {
        $this->getEntityManager()->persist($raCandidate);
        $this->getEntityManager()->flush();
    }

    public function removeByIdentityId(IdentityId $identityId)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->delete($this->_entityName, 'rac')
            ->where('rac.identityId = :identityId')
            ->setParameter('identityId', (string) $identityId)
            ->getQuery()
            ->execute();

        $this->getEntityManager()->flush();
    }
}

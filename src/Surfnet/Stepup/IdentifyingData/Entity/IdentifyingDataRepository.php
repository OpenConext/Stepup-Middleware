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

namespace Surfnet\Stepup\IdentifyingData\Entity;

use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;

class IdentifyingDataRepository extends EntityRepository
{
    /**
     * @param IdentifyingDataId $id
     * @return IdentifyingData
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getById(IdentifyingDataId $id)
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :identifyingDataId')
            ->setParameter('identifyingDataId', (string) $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param IdentifyingData $sensitiveData
     */
    public function save(IdentifyingData $sensitiveData)
    {
        $this->getEntityManager()->persist($sensitiveData);
        $this->getEntityManager()->flush();
    }
}

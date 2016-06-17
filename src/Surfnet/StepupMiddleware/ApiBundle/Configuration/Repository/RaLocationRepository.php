<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Query\RaLocationQuery;

class RaLocationRepository extends EntityRepository
{
    /**
     * @param RaLocationQuery $query
     * @return null|RaLocation[]
     */
    public function findByInstitution(RaLocationQuery $query)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('rl')
            ->from('Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation', 'rl')
            ->where('rl.institution = :institution')
            ->setParameter('institution', $query->institution->getInstitution())
            ->getQuery()
            ->getResult();
    }

    /**
     * @param RaLocation $raLocation
     */
    public function save(RaLocation $raLocation)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($raLocation);
        $entityManager->flush();
    }
}

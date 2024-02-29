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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Query\RaLocationQuery;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;

class RaLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RaLocation::class);
    }

    /**
     * @return null|RaLocation[]
     */
    public function search(RaLocationQuery $query)
    {
        if (!in_array($query->orderBy, ['name', 'location', 'contact_information'])) {
            throw new RuntimeException(sprintf('Unknown order by column "%s"', $query->orderBy));
        }

        $orderBy = 'rl.' . $query->orderBy;
        $orderDirection = $query->orderDirection === 'asc' ? 'ASC' : 'DESC';

        return $this->getEntityManager()->createQueryBuilder()
            ->select('rl')
            ->from(RaLocation::class, 'rl')
            ->where('rl.institution = :institution')
            ->setParameter('institution', $query->institution->getInstitution())
            ->orderBy($orderBy, $orderDirection)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RaLocation[]
     */
    public function findByRaLocationId(RaLocationId $raLocationId)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.id = :id')
            ->setParameter('id', $raLocationId->getRaLocationId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(RaLocation $raLocation): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($raLocation);
        $entityManager->flush();
    }

    public function remove(RaLocation $raLocation): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($raLocation);
        $entityManager->flush();
    }

    /**
     * @return RaLocation[]
     */
    public function findByInstitution(Institution $institution)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getResult();
    }

    public function removeRaLocationsFor(Institution $institution): void
    {
        $this->createQueryBuilder('rl')
            ->delete()
            ->where('rl.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->execute();
    }
}

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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\WhitelistEntry;

class WhitelistEntryRepository extends EntityRepository
{
    /**
     * @param Institution[] $institutions
     * @return array
     */
    public function findEntriesByInstitutions(array $institutions)
    {
        $qb = $this->createQueryBuilder('w');

        return $qb
            ->where($qb->expr()->in('w.institution', $institutions))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param WhitelistEntry[] $whitelistEntries
     */
    public function saveEntries(array $whitelistEntries)
    {
        $entityManager = $this->getEntityManager();

        foreach ($whitelistEntries as $whitelistEntry) {
            $entityManager->persist($whitelistEntry);
        }

        $entityManager->flush();
    }

    /**
     * Removes all WhitelistEntries
     */
    public function removeAll()
    {
        $this->createQueryBuilder('w')
            ->delete()
            ->where('1 = 1')
            ->getQuery()
            ->execute();
    }

    /**
     * @param WhitelistEntry[] $whitelistEntries
     */
    public function remove(array $whitelistEntries)
    {
        $entityManager = $this->getEntityManager();

        foreach ($whitelistEntries as $whitelistEntry) {
            $entityManager->remove($whitelistEntry);
        }

        $entityManager->flush();
    }
}

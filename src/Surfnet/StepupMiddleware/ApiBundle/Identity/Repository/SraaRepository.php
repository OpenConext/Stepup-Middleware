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
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

/**
 * @extends ServiceEntityRepository<Sraa>
 */
class SraaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sraa::class);
    }

    /**
     * Removes all SRAA's from the database
     */
    public function removeAll(): void
    {
        $this
            ->getEntityManager()
            ->createQuery(
                'DELETE FROM '.Sraa::class,
            )
            ->execute();

        $this->getEntityManager()->clear();
    }

    /**
     * Saves all SRAAs to the database, using inserts only
     */
    public function saveAll(array $sraaList): void
    {
        $invalid = [];
        foreach ($sraaList as $index => $sraa) {
            if (!$sraa instanceof Sraa) {
                $invalid[$index] = $sraa;
            }
        }

        if ($invalid !== []) {
            $invalidIndications = [];
            foreach ($invalid as $index => $value) {
                $invalidIndications[] = sprintf(
                    '"%s" at index "%d"',
                    get_debug_type($value),
                    $index
                );
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Expected array of Raa Objects, got %s',
                    implode(', ', $invalidIndications),
                ),
            );
        }

        $entityManager = $this->getEntityManager();

        foreach ($sraaList as $sraa) {
            $entityManager->persist($sraa);
        }

        $entityManager->flush();
    }

    public function findByNameId(NameId $nameId): ?Sraa
    {
        return $this->findOneBy(['nameId' => (string)$nameId]);
    }

    /**
     * @return boolean
     */
    public function contains(NameId $nameId): bool
    {
        return $this->findByNameId($nameId) !== null;
    }
}

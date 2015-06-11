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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

class SraaRepository extends EntityRepository
{
    /**
     * Removes all SRAA's from the database
     */
    public function removeAll()
    {
        $this
            ->getEntityManager()
            ->createQuery(
                'DELETE FROM Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa'
            )
            ->execute();
    }

    /**
     * Saves all SRAAs to the database, using inserts only
     *
     * @param array $sraaList
     */
    public function saveAll(array $sraaList)
    {
        $invalid = [];
        foreach ($sraaList as $index => $sraa) {
            if (!$sraa instanceof Sraa) {
                $invalid[$index] = $sraa;
            }
        }

        if (count($invalid)) {
            $invalidIndications = [];
            foreach ($invalid as $index => $value) {
                $invalidIndications[] = sprintf(
                    '"%s" at index "%d"',
                    is_object($value) ? get_class($value) : gettype($value)
                );
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Expected array of Raa Objects, got %s',
                    implode(', ', $invalidIndications)
                )
            );
        }

        $entityManager = $this->getEntityManager();

        foreach ($sraaList as $sraa) {
            $entityManager->persist($sraa);
        }

        $entityManager->flush();
    }

    /**
     * @param NameId $nameId
     * @return null|\Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa
     */
    public function findByNameId(NameId $nameId)
    {
        return $this->findOneBy(['nameId' => (string) $nameId]);
    }

    /**
     * @param NameId $nameId
     * @return boolean
     */
    public function contains(NameId $nameId)
    {
        return $this->findOneBy(['nameId' => (string) $nameId]) !== null;
    }
}

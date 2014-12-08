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
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

class SraaRepository extends EntityRepository
{
    public function removeAll()
    {
        $this
            ->getEntityManager()
            ->createQuery(
                'DELETE FROM Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa'
            )
            ->execute();
    }

    public function saveAll($sraaList)
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
}

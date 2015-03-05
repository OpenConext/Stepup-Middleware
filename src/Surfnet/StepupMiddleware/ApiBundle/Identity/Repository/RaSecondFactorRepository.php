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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchRaSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;

class RaSecondFactorRepository extends EntityRepository
{
    /**
     * @param string $id
     * @return RaSecondFactor|null
     */
    public function find($id)
    {
        /** @var RaSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @param string $identityId
     * @return RaSecondFactor[]
     */
    public function findByIdentityId($identityId)
    {
        return parent::findBy(['identityId' => $identityId]);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) The amount of if statements do not necessarily make the method
     *                                               below complex or hard to maintain.
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param SearchRaSecondFactorCommand $command
     * @return Query
     */
    public function createSearchQuery(SearchRaSecondFactorCommand $command)
    {
        $queryBuilder = $this
            ->createQueryBuilder('sf')
            ->andWhere('sf.institution = :institution')
            ->setParameter('institution', $command->institution);

        if ($command->name) {
            $queryBuilder->andWhere('sf.name LIKE :name')->setParameter('name', "%$command->name%");
        }

        if ($command->type) {
            $queryBuilder->andWhere('sf.type = :type')->setParameter('type', $command->type);
        }

        if ($command->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.secondFactorId = :secondFactorId')
                ->setParameter('secondFactorId', $command->secondFactorId);
        }

        if ($command->email) {
            $queryBuilder->andWhere('sf.email LIKE :email')->setParameter('email', "%$command->email%");
        }

        if ($command->status) {
            $queryBuilder->andWhere('sf.status = :status')->setParameter('status', $command->status);
        }

        switch ($command->orderBy) {
            case 'name':
            case 'type':
            case 'secondFactorId':
            case 'email':
            case 'status':
                $queryBuilder->orderBy("sf.$command->orderBy", $command->orderDirection === 'desc' ? 'DESC' : 'ASC');
                break;
        }

        return $queryBuilder->getQuery();
    }

    public function save(RaSecondFactor $secondFactor)
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    /**
     * @param RaSecondFactor[] $secondFactors
     */
    public function saveAll(array $secondFactors)
    {
        $entityManager = $this->getEntityManager();

        foreach ($secondFactors as $secondFactor) {
            $entityManager->persist($secondFactor);
        }

        $entityManager->flush();
    }
}

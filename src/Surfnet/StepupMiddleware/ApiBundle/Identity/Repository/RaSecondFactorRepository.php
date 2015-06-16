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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;

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
     * @param RaSecondFactorQuery $query
     * @return Query
     */
    public function createSearchQuery(RaSecondFactorQuery $query)
    {
        $queryBuilder = $this
            ->createQueryBuilder('sf')
            ->andWhere('sf.institution = :institution')
            ->setParameter('institution', $query->institution);

        if ($query->name) {
            $queryBuilder->andWhere('sf.name LIKE :name')->setParameter('name', sprintf('%%%s%%', $query->name));
        }

        if ($query->type) {
            $queryBuilder->andWhere('sf.type = :type')->setParameter('type', $query->type);
        }

        if ($query->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.secondFactorId = :secondFactorId')
                ->setParameter('secondFactorId', $query->secondFactorId);
        }

        if ($query->email) {
            $queryBuilder->andWhere('sf.email LIKE :email')->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if ($query->status) {
            $queryBuilder->andWhere('sf.status = :status')->setParameter('status', $query->status);
        }

        switch ($query->orderBy) {
            case 'name':
            case 'type':
            case 'secondFactorId':
            case 'email':
            case 'status':
                $queryBuilder->orderBy(
                    sprintf('sf.%s', $query->orderBy),
                    $query->orderDirection === 'desc' ? 'DESC' : 'ASC'
                );
                break;
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param IdentityId $identityId
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'rasf')
            ->where('rasf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
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

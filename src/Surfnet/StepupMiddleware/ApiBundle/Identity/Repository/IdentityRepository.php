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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;

class IdentityRepository extends EntityRepository
{
    /**
     * @param string $id
     * @return Identity|null
     */
    public function find($id)
    {
        /** @var Identity|null $identity */
        $identity = parent::find($id);

        return $identity;
    }

    /**
     * @param Identity $identity
     */
    public function save(Identity $identity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($identity);
        $entityManager->flush();
    }

    /**
     * @param IdentityQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(IdentityQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('i');

        $queryBuilder
            ->where('i.institution = :institution')
            ->setParameter('institution', $query->institution);

        if ($query->nameId) {
            $queryBuilder
                ->andWhere('i.nameId = :nameId')
                ->setParameter('nameId', $query->nameId);
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.email, :email) > 0')
                ->setParameter('email', $query->email);
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.commonName, :commonName) > 0')
                ->setParameter('commonName', $query->commonName);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string[] $nameIds
     * @return Identity[] Indexed by NameID.
     */
    public function findByNameIdsIndexed(array $nameIds)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('i')
            ->from('Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity', 'i', 'i.nameId')
            ->where('i.nameId IN (:nameIds)')
            ->setParameter('nameIds', $nameIds)
            ->getQuery()
            ->getResult();
    }
}

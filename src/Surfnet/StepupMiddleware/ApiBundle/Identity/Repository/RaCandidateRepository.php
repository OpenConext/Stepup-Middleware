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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;

class RaCandidateRepository extends EntityRepository
{
    /**
     * @param RaCandidate $raCandidate
     * @return void
     */
    public function save(RaCandidate $raCandidate)
    {
        $this->getEntityManager()->persist($raCandidate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param IdentityId $identityId
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId)
    {
        $raCandidate = $this->findByIdentityId($identityId);

        if (!$raCandidate) {
            return;
        }

        $this->getEntityManager()->remove($raCandidate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string[] $nameIds
     * @return void
     */
    public function removeByNameIds($nameIds)
    {
        $raCandidates = $this->findByNameIds($nameIds);

        $em = $this->getEntityManager();
        foreach ($raCandidates as $raCandidate) {
            $em->remove($raCandidate);
        }

        $em->flush();
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(RaCandidateQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rac')
            ->where('rac.institution = :institution')
            ->setParameter('institution', $query->institution);

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(rac.commonName, :commonName) > 0')
                ->setParameter('commonName', $query->commonName);
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(rac.email, :email) > 0')
                ->setParameter('email', $query->email);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string[] $sraaList
     * @return RaCandidate[]
     */
    public function findByNameIds(array $sraaList)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.nameId IN (:sraaList)')
            ->setParameter('sraaList', $sraaList)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $identityId
     * @return null|RaCandidate
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByIdentityId($identityId)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.identityId = :identityId')
            ->setParameter('identityId', $identityId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

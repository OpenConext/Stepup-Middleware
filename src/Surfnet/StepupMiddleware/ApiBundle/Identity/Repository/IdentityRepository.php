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
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;

/**
 * @extends ServiceEntityRepository<Identity>
 */
class IdentityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Identity::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?Identity
    {
        /** @var Identity|null $identity */
        $identity = parent::find($id);

        return $identity;
    }

    public function save(Identity $identity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($identity);
        $entityManager->flush();
    }

    /**
     * @return Query
     */
    public function createSearchQuery(
        IdentityQuery $query,
    ): Query {
        $queryBuilder = $this->createQueryBuilder('i');

        if ($query->institution) {
            $queryBuilder
                ->andWhere('i.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->nameId) {
            $queryBuilder
                ->andWhere('i.nameId = :nameId')
                ->setParameter('nameId', $query->nameId);
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('i.email LIKE :email')
                ->setParameter('email', sprintf('%%%s%%', $query->email));
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('i.commonName LIKE :commonName')
                ->setParameter('commonName', sprintf('%%%s%%', $query->commonName));
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string[] $nameIds
     * @return Identity[] Indexed by NameID.
     */
    public function findByNameIdsIndexed(array $nameIds): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('i')
            ->from(Identity::class, 'i', 'i.nameId')
            ->where('i.nameId IN (:nameIds)')
            ->setParameter('nameIds', $nameIds)
            ->getQuery()
            ->getResult();
    }

    /**
     *
     * @return bool
     */
    public function hasIdentityWithNameIdAndInstitution(NameId $nameId, Institution $institution): bool
    {
        $identityCount = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.nameId = :nameId')
            ->andWhere('i.institution = :institution')
            ->setParameter('nameId', $nameId->getNameId())
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getSingleScalarResult();

        return $identityCount > 0;
    }

    /**
     * @return Identity
     */
    public function findOneByNameIdAndInstitution(NameId $nameId, Institution $institution): Identity
    {
        return $this->createQueryBuilder('i')
            ->where('i.nameId = :nameId')
            ->setParameter('nameId', $nameId->getNameId())
            ->andWhere('i.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getSingleResult();
    }

    public function findOneByNameId(string $nameId): ?Identity
    {
        return $this->findOneBy(['nameId' => $nameId]);
    }

    public function updateStatusByIdentityIdToForgotten(IdentityId $identityId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update($this->getEntityName(), 'i')
            ->set('i.commonName', ":name")
            ->set('i.email', ":email")
            ->where('i.id = :id')
            ->setParameter('id', $identityId->getIdentityId())
            ->setParameter('name', CommonName::unknown())
            ->setParameter('email', Email::unknown())
            ->getQuery()
            ->execute();
    }
}

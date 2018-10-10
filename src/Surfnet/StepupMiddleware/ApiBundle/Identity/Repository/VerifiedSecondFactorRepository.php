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

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;

class VerifiedSecondFactorRepository extends EntityRepository
{
    /**
     * @var InstitutionAuthorizationRepositoryFilter
     */
    private $authorizationRepositoryFilter;

    /**
     * VerifiedSecondFactorRepository constructor.
     * @param EntityManager $em
     * @param Mapping\ClassMetadata $class
     * @param InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
     */
    public function __construct(
        EntityManager $em,
        Mapping\ClassMetadata $class,
        InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
    ) {
        parent::__construct($em, $class);
        $this->authorizationRepositoryFilter = $authorizationRepositoryFilter;
    }


    /**
     * @param string $id
     * @return VerifiedSecondFactor|null
     */
    public function find($id)
    {
        /** @var VerifiedSecondFactor|null $secondFactor */
        $secondFactor = parent::find($id);

        return $secondFactor;
    }

    /**
     * @param DateTime $requestedAt
     * @return VerifiedSecondFactor[]
     */
    public function findByDate(DateTime $requestedAt)
    {
        $fromDate = clone $requestedAt;
        $fromDate->setTime(0, 0, 0);

        $toDate = clone $requestedAt;
        $toDate->setTime(23, 59, 59);

        return $this->createQueryBuilder('sf')
            ->where('sf.registrationRequestedAt <= :toDate')
            ->andWhere('sf.registrationRequestedAt >= :fromDate')
            ->setParameter('toDate', $toDate)
            ->setParameter('fromDate', $fromDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param VerifiedSecondFactorQuery $query
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @param InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
     * @return Query
     */
    public function createSearchQuery(VerifiedSecondFactorQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        // Modify query to filter on authorization
        $this->authorizationRepositoryFilter->filter($queryBuilder, $query->authorizationContext, 'sf.id', 'sf.institution', 'iac');

        if ($query->identityId) {
            $queryBuilder
                ->andWhere('sf.identityId = :identityId')
                ->setParameter('identityId', (string) $query->identityId);
        }

        if ($query->secondFactorId) {
            $queryBuilder
                ->andWhere('sf.id = :secondFactorId')
                ->setParameter('secondFactorId', (string) $query->secondFactorId);
        }

        if (is_string($query->registrationCode)) {
            $queryBuilder
                ->andWhere('sf.registrationCode = :registrationCode')
                ->setParameter('registrationCode', $query->registrationCode);
        }

        return $queryBuilder->getQuery();
    }

    public function removeByIdentityId(IdentityId $identityId)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'sf')
            ->where('sf.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    /**
     * @param VerifiedSecondFactor $secondFactor
     */
    public function save(VerifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    public function remove(VerifiedSecondFactor $secondFactor)
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}

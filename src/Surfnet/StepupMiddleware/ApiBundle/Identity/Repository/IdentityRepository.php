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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchIdentityCommand;

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
     * @param SearchIdentityCommand $command
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(SearchIdentityCommand $command)
    {
        $queryBuilder = $this->createQueryBuilder('i');

        $queryBuilder
            ->where('i.institution = :institution')
            ->setParameter('institution', $command->institution);

        if ($command->nameId) {
            $queryBuilder
                ->andWhere('i.nameId = :nameId')
                ->setParameter('nameId', $command->nameId);
        }

        if ($command->email) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.email, :email) > 0')
                ->setParameter('email', $command->email);
        }

        if ($command->commonName) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.commonName, :commonName) > 0')
                ->setParameter('commonName', $command->commonName);
        }

        return $queryBuilder->getQuery();
    }
}

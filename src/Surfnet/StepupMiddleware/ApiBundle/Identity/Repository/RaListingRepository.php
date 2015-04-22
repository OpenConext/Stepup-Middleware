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
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchRaListingCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;

class RaListingRepository extends EntityRepository
{
    public function save(RaListing $raListingEntry)
    {
        $this->getEntityManager()->persist($raListingEntry);
        $this->getEntityManager()->flush();
    }

    /**
     * @param SearchRaListingCommand $searchCommand
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(SearchRaListingCommand $searchCommand)
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.institution = :institution')
            ->setParameter('institution', $searchCommand->institution);

        $orderDirection = $searchCommand->orderDirection === 'asc' ? 'ASC' : 'DESC';
        switch ($searchCommand->orderBy) {
            case 'commonName':
                $queryBuilder->orderBy('r.commonName', $orderDirection);
                break;
            default:
                throw new RuntimeException(sprintf('Unknown order by column "%s"', $searchCommand->orderBy));
        }

        return $queryBuilder->getQuery();
    }
}

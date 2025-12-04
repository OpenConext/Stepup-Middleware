<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Pagerfanta\Pagerfanta;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\NotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RecoveryToken;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RecoveryTokenQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

/** @extends AbstractSearchService<RecoveryToken> */
class RecoveryTokenService extends AbstractSearchService
{
    public function __construct(private readonly RecoveryTokenRepository $recoveryTokenRepository)
    {
    }

    /**
     * @return Pagerfanta<RecoveryToken>
     */
    public function search(RecoveryTokenQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->recoveryTokenRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    public function get(RecoveryTokenId $id): RecoveryToken
    {
        $recoveryToken = $this->recoveryTokenRepository->find($id);
        if (!$recoveryToken) {
            throw new NotFoundException(sprintf('Unable to find Recovery Token with id %s', $id));
        }
        return $recoveryToken;
    }

    public function getFilterOptions(RecoveryTokenQuery $query): array
    {
        return $this->getFilteredQueryOptions($this->recoveryTokenRepository->createOptionsQuery($query));
    }

    public function identityHasActiveRecoveryToken(Identity $identity): bool
    {
        $recoveryTokens = $this->recoveryTokenRepository->findBy(
            [
                'identityId' => $identity->id,
                'status' => RecoveryTokenStatus::active(),
            ],
        );

        return $recoveryTokens !== [];
    }
}

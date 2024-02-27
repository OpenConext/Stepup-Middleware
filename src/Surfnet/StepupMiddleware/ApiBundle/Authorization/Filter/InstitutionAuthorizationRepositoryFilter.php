<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter;

use Doctrine\ORM\QueryBuilder;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;

class InstitutionAuthorizationRepositoryFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @param string $institutionField
     * @param string $authorizationAlias
     */
    public function filter(
        QueryBuilder $queryBuilder,
        InstitutionAuthorizationContextInterface $authorizationContext,
        $institutionField,
        $authorizationAlias
    ): void {
        // If actor is SRAA we don't need filtering
        if ($authorizationContext->isActorSraa()) {
            return;
        }

        // Else filter on institutions we are allowed to manage
        $values = [];
        foreach ($authorizationContext->getInstitutions() as $institution) {
            $values[] = (string)$institution;
        }

        $parameter = $this->getParameterName($authorizationAlias, 'institutions');

        $whereCondition = sprintf(
            '%s IN (:%s)',
            $institutionField,
            $parameter
        );

        $queryBuilder->andWhere($whereCondition);
        $queryBuilder->setParameter($parameter, $values);
    }

    /**
     * @param $authorizationAlias
     * @param $name
     * @return string
     */
    private function getParameterName($authorizationAlias, string $name): string
    {
        return "{$authorizationAlias}_{$name}";
    }
}

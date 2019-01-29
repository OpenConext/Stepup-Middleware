<?php

/**
 * Copyright 2019 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Value;

use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;

class AuthorizedInstitutionCollection
{
    /**
     * Mapped on institution, a list of authorizations for the institution relation
     *
     * @example
     * [
     *      'institution-1' => [use_ra, use_raa, select_raa],
     *      'institution-2' => [use_ra],
     *      'institution-3' => [select_raa],
     * ]
     *
     * @var AuthorityRole[]
     */
    private $authorizations = [];

    /**
     * @param RaListing[] $authorizations
     * @return AuthorizedInstitutionCollection
     */
    public static function fromInstitutionAuthorization($authorizations)
    {
        $collection = new self();

        foreach ($authorizations as $authorization) {
            $collection->authorizations[(string) $authorization->raInstitution][] = (string) $authorization->role;
        }
        return $collection;
    }

    public function getAuthorizations()
    {
        return $this->authorizations;
    }
}

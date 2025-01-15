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

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;

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
     * @var array<string, array<string>>
     */
    private array $authorizations = [];

    public static function from(
        InstitutionCollection $raInstitutions,
        ?InstitutionCollection $raaInstitutions = null,
    ): self {
        $collection = new self();

        /** @var string $institution */
        foreach ($raInstitutions as $institution) {
            $collection->authorizations[(string)$institution][] = AuthorityRole::ROLE_RA;
        }
        if ($raaInstitutions instanceof InstitutionCollection) {
            /** @var string $institution */
            foreach ($raaInstitutions as $institution) {
                // Override existing lower role
                if (isset($collection->authorizations[(string)$institution])
                    && in_array(AuthorityRole::ROLE_RA, $collection->authorizations[(string)$institution])
                ) {
                    $collection->authorizations[(string)$institution] = [];
                }
                $collection->authorizations[(string)$institution][] = AuthorityRole::ROLE_RAA;
            }
        }
        return $collection;
    }

    public function getAuthorizations(): array
    {
        return $this->authorizations;
    }
}

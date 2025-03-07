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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Value;

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;

final class InstitutionRoleSet implements InstitutionRoleSetInterface
{
    /**
     * @var InstitutionRole[]
     */
    private readonly array $institutionRoles;

    public function __construct(array $institutionRoles)
    {
        foreach ($institutionRoles as $role) {
            if (!$role instanceof InstitutionRole) {
                throw InvalidArgumentException::invalidType(
                    'InsititutionRole[]',
                    'institutionRoles',
                    $institutionRoles,
                );
            }
        }
        $this->institutionRoles = $institutionRoles;
    }

    public function getRoles(): array
    {
        return $this->institutionRoles;
    }
}

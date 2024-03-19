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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\Identity\Value\Institution;

final class RegistrationAuthorityCollection
{
    /**
     * @var RegistrationAuthority[]
     */
    private array $registrationAuthorities = [];

    public function set(Institution $institution, RegistrationAuthority $registrationAuthority): void
    {
        $this->registrationAuthorities[(string)$institution] = $registrationAuthority;
    }

    public function get(Institution $institution): RegistrationAuthority
    {
        return $this->registrationAuthorities[(string)$institution];
    }

    public function exists(Institution $institution): bool
    {
        return array_key_exists((string)$institution, $this->registrationAuthorities);
    }

    public function remove(Institution $institution): void
    {
        unset($this->registrationAuthorities[(string)$institution]);
    }

    public function count(): int
    {
        return count($this->registrationAuthorities);
    }

    /**
     * RegistrationAuthority[]
     */
    public function getValues(): array
    {
        return array_values($this->registrationAuthorities);
    }
}

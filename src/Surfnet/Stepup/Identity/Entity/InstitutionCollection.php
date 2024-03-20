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

use Surfnet\Stepup\Identity\Collection\InstitutionCollection as Institutions;
use Surfnet\Stepup\Identity\Value\Institution;

final class InstitutionCollection
{
    private array $institutions = [];

    public function set(Institution $institution): void
    {
        $this->institutions[(string)$institution] = $institution;
    }

    public function update(Institutions $institutions): void
    {
        foreach ($institutions as $institution) {
            $this->institutions[(string)$institution] = $institution;
        }
    }

    public function get(Institution $institution): Institution
    {
        return $this->institutions[(string)$institution];
    }

    public function exists(Institution $institution): bool
    {
        return array_key_exists((string)$institution, $this->institutions);
    }

    public function remove(Institution $institution): void
    {
        unset($this->institutions[(string)$institution]);
    }

    public function count(): int
    {
        return count($this->institutions);
    }

    /**
     * @return Institution[]
     */
    public function institutions(): array
    {
        return $this->institutions;
    }
}

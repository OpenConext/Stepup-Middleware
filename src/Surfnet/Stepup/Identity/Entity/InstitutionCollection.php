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

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\Institution;
use \Surfnet\Stepup\Identity\Collection\InstitutionCollection as Institutions;

final class InstitutionCollection
{
    /**
     * @var InstitutionRole[]
     */
    private $institutions = [];

    /**
     * @param Institution $institution
     */
    public function set(Institution $institution)
    {
        $this->institutions[(string)$institution] = $institution;
    }

    /**
     * @param Institutions $institutions
     */
    public function update(Institutions $institutions)
    {
        foreach ($institutions as $institution) {
            $this->institutions[(string)$institutions] = $institution;
        }
    }

    /**
     * @param Institution $institution
     * @return Institution
     */
    public function get(Institution $institution)
    {
        return $this->institutions[(string)$institution];
    }

    /**
     * @param Institution $institution
     * @return Institution
     */
    public function exists(Institution $institution)
    {
        return array_key_exists((string)$institution, $this->institutions);
    }

    /**
     * @param Institution $institution
     */
    public function remove(Institution $institution)
    {
        unset($this->institutions[(string)$institution]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->institutions);
    }

    /**
     * @return InstitutionRole[]
     */
    public function institutions()
    {
        return $this->institutions;
    }
}

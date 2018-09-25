<?php
/**
 * Copyright 2018 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not select this file except in compliance with the License.
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

namespace Surfnet\Stepup\Configuration\Value;

use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionRole implements JsonSerializable
{
    const ROLE_USE_RA = 'use_ra';
    const ROLE_USE_RAA = 'use_raa';
    const ROLE_SELECT_RAA = 'select_raa';

    private static $validRoles = [
        self::ROLE_USE_RA,
        self::ROLE_USE_RAA,
        self::ROLE_SELECT_RAA,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * InstitutionRole constructor.
     * @param $type
     */
    private function __construct($type)
    {
        if (!in_array($type, self::$validRoles)) {
            throw new InvalidArgumentException();
        }

        $this->type = $type;
    }

    /**
     * @param string $type
     * @return InstitutionRole
     */
    public static function create($type)
    {
        return new self($type);
    }

    /**
     * @return InstitutionRole
     */
    public static function useRa()
    {
        return new self(self::ROLE_USE_RA);
    }

    /**
     * @return InstitutionRole
     */
    public static function useRaa()
    {
        return new self(self::ROLE_USE_RAA);
    }

    /**
     * @return InstitutionRole
     */
    public static function selectRaa()
    {
        return new self(self::ROLE_SELECT_RAA);
    }

    /**
     * @param InstitutionRole $role
     * @return bool
     */
    public function equals(InstitutionRole $role)
    {
        return $this->type == $role->getType();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function jsonSerialize()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->type;
    }
}

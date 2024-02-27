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
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionRole implements JsonSerializable, Stringable
{
    public const ROLE_USE_RA = 'use_ra';
    public const ROLE_USE_RAA = 'use_raa';
    public const ROLE_SELECT_RAA = 'select_raa';

    private static array $validRoles = [
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
    public function __construct($type)
    {
        if (!in_array($type, self::$validRoles)) {
            throw new InvalidArgumentException();
        }

        $this->type = $type;
    }

    /**
     * @return InstitutionRole
     */
    public static function useRa(): self
    {
        return new self(self::ROLE_USE_RA);
    }

    /**
     * @return InstitutionRole
     */
    public static function useRaa(): self
    {
        return new self(self::ROLE_USE_RAA);
    }

    /**
     * @return InstitutionRole
     */
    public static function selectRaa(): self
    {
        return new self(self::ROLE_SELECT_RAA);
    }

    /**
     * @return bool
     */
    public function equals(InstitutionRole $role): bool
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
    public function __toString(): string
    {
        return $this->type;
    }
}

<?php
/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Value;

use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionAuthorizationOption implements JsonSerializable
{
    /**
     * @var InstitutionRole
     */
    private $institutionRole;

    /**
     * @var InstitutionSet
     */
    private $institutionSet;

    /**
     * @var boolean
     */
    private $isDefault;

    /**
     * If the default is set to true then the object will use the old default behaviour. That behaviour is that it
     * will take the current institution into account when returning institutions.
     *
     * AbstractRoleOption constructor.
     * @param InstitutionRole $role
     * @param InstitutionSet $institutionSet
     * @param bool $isDefault
     */
    private function __construct(InstitutionRole $role, InstitutionSet $institutionSet, $isDefault)
    {
        $this->institutionRole = $role;
        $this->institutionSet = $institutionSet;
        $this->isDefault = (bool)$isDefault;
    }

    /**
     * @param InstitutionRole $role
     * @param string[]|null
     * @return InstitutionAuthorizationOption
     */
    public static function fromInstitutionConfig(InstitutionRole $role, $institutions = null)
    {
        if (is_null($institutions)) {
            return self::getDefault($role);
        }

        if (!is_array($institutions)) {
            throw InvalidArgumentException::invalidType(
                'array',
                'institutions',
                $institutions
            );
        }

        array_walk(
            $institutions,
            function ($institution, $key) use ($institutions) {
                if (!is_string($institution)  || strlen(trim($institution)) === 0) {
                    throw InvalidArgumentException::invalidType(
                        'string',
                        'institutions',
                        $institutions[$key]
                    );
                }
            }
        );

        $set = [];
        foreach ($institutions as $institutionTitle) {
            $set[] = new Institution($institutionTitle);
        }

        $institutionSet = InstitutionSet::create($set);

        return new self($role, $institutionSet, false);
    }

    /**
     * @param InstitutionRole $role
     * @param Institution $institution
     * @param Institution[] $institutions
     * @return InstitutionAuthorizationOption
     */
    public static function fromInstitutions(InstitutionRole $role, Institution $institution, array $institutions)
    {
        if (count($institutions) == 1 && current($institutions)->getInstitution() === $institution->getInstitution()) {
            return new self($role, InstitutionSet::create([]), true);
        }
        return new self($role, InstitutionSet::create($institutions), false);
    }

    /**
     * @param InstitutionRole $role
     * @param string[]|null
     * @return InstitutionAuthorizationOption
     */
    public static function getDefault(InstitutionRole $role)
    {
        return new self($role, InstitutionSet::create([]), true);
    }

    /**
     * @param InstitutionRole $role
     * @param string[]|null
     * @return InstitutionAuthorizationOption
     */
    public static function getEmpty(InstitutionRole $role)
    {
        return new self($role, InstitutionSet::create([]), false);
    }

    /**
     * @return null
     */
    public static function blank()
    {
        return null;
    }

    /**
     * @param InstitutionAuthorizationOption $option
     * @return bool
     */
    public function equals(InstitutionAuthorizationOption $option)
    {
        return
            $this->institutionRole->equals($option->getInstitutionRole()) &&
            $this->institutionSet->equals($option->getInstitutionSet()) &&
            $this->isDefault === $option->isDefault();
    }

    /**
     * @return InstitutionRole
     */
    public function getInstitutionRole()
    {
        return $this->institutionRole;
    }

    /**
     * @return InstitutionSet
     */
    public function getInstitutionSet()
    {
        return $this->institutionSet;
    }

    /**
     * If the default is set to true then the object will use the old default behaviour. That behaviour is that it
     * will take the current institution into account and this method will return the current institution.
     *
     * @param Institution $institution
     * @return Institution[]
     */
    public function getInstitutions(Institution $institution)
    {
        if ($this->isDefault) {
            return [$institution];
        }
        return $this->institutionSet->getInstitutions();
    }

    /**
     * @param Institution $institution
     * @param Institution $default
     * @return bool
     */
    public function hasInstitution(Institution $institution, Institution $default)
    {
        $institutions = $this->getInstitutions($default);
        $list = array_map(
            function (Institution $institution) {
                return $institution->getInstitution();
            },
            $institutions
        );

        if (!in_array($institution->getInstitution(), $list)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    public function jsonSerialize()
    {
        if ($this->isDefault) {
            return null;
        }
        return $this->institutionSet->toScalarArray();
    }
}

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
    private readonly bool $isDefault;

    /**
     * If the default is set to true then the object will use the old default behaviour. That behaviour is that it
     * will take the current institution into account when returning institutions.
     *
     * AbstractRoleOption constructor.
     */
    private function __construct(
        private readonly InstitutionRole $institutionRole,
        private readonly InstitutionSet  $institutionSet,
        bool                             $isDefault,
    ) {
        $this->isDefault = (bool)$isDefault;
    }

    public static function fromInstitutionConfig(InstitutionRole $role, $institutions = null): InstitutionAuthorizationOption
    {
        if (is_null($institutions)) {
            return self::getDefault($role);
        }

        if (!is_array($institutions)) {
            throw InvalidArgumentException::invalidType(
                'array',
                'institutions',
                $institutions,
            );
        }

        array_walk(
            $institutions,
            function ($institution, $key) use ($institutions): void {
                if (!is_string($institution) || trim($institution) === '') {
                    throw InvalidArgumentException::invalidType(
                        'string',
                        'institutions',
                        $institutions[$key],
                    );
                }
            },
        );

        $set = [];
        foreach ($institutions as $institutionTitle) {
            $set[] = new Institution($institutionTitle);
        }

        $institutionSet = InstitutionSet::create($set);

        return new self($role, $institutionSet, false);
    }

    /**
     * @param Institution[] $institutions
     */
    public static function fromInstitutions(InstitutionRole $role, Institution $institution, array $institutions): self
    {
        if (count($institutions) == 1 && current($institutions)->getInstitution() === $institution->getInstitution()) {
            return new self($role, InstitutionSet::create([]), true);
        }
        return new self($role, InstitutionSet::create($institutions), false);
    }

    public static function getDefault(InstitutionRole $role): self
    {
        return new self($role, InstitutionSet::create([]), true);
    }

    public static function getEmpty(InstitutionRole $role): self
    {
        return new self($role, InstitutionSet::create([]), false);
    }

    public static function blank(): null
    {
        return null;
    }

    public function equals(InstitutionAuthorizationOption $option): bool
    {
        return
            $this->institutionRole->equals($option->getInstitutionRole()) &&
            $this->institutionSet->equals($option->getInstitutionSet()) &&
            $this->isDefault === $option->isDefault();
    }

    public function getInstitutionRole(): InstitutionRole
    {
        return $this->institutionRole;
    }

    public function getInstitutionSet(): InstitutionSet
    {
        return $this->institutionSet;
    }

    /**
     * If the default is set to true then the object will use the old default behaviour. That behaviour is that it
     * will take the current institution into account and this method will return the current institution.
     *
     * @return Institution[]
     */
    public function getInstitutions(Institution $institution): array
    {
        if ($this->isDefault) {
            return [$institution];
        }
        return $this->institutionSet->getInstitutions();
    }

    public function hasInstitution(Institution $institution, Institution $default): bool
    {
        $institutions = $this->getInstitutions($default);
        $list = array_map(
            fn(Institution $institution) => $institution->getInstitution(),
            $institutions,
        );
        return in_array($institution->getInstitution(), $list);
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function jsonSerialize(): ?array
    {
        if ($this->isDefault) {
            return null;
        }
        return $this->institutionSet->toScalarArray();
    }
}

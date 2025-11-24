<?php

declare(strict_types=1);

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

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionSet
{
    /**
     * @var Institution[]
     */
    private readonly array $institutions;

    /**
     * @param Institution[] $institutions
     */
    private function __construct(array $institutions)
    {
        if ($institutions !== array_unique($institutions)) {
            throw new InvalidArgumentException('Duplicate entries are not allowed in the InstitutionSet');
        }

        $this->institutions = $this->sort($institutions);
    }

    /**
     * @param Institution[] $institutions
     */
    public static function create(array $institutions): self
    {
        // Verify only institutions are collected in the set
        array_walk(
            $institutions,
            function ($institution, $key) use ($institutions): void {
                if (!$institution instanceof Institution) {
                    throw InvalidArgumentException::invalidType(
                        Institution::class,
                        'institutions',
                        $institutions[$key],
                    );
                }
            },
        );

        return new self($institutions);
    }

    public function equals(InstitutionSet $other): bool
    {
        return $this->toScalarArray() === $other->toScalarArray();
    }

    public function isOption(Institution $institution): bool
    {
        return in_array($institution->getInstitution(), $this->institutions);
    }

    /**
     * @return Institution[]
     */
    public function getInstitutions(): array
    {
        return $this->institutions;
    }

    public function toScalarArray(): array
    {
        return array_map(strval(...), $this->institutions);
    }

    /**
     * @param Institution[] $institutions
     * @return Institution[]
     */
    private function sort(array $institutions): array
    {
        usort(
            $institutions,
            fn(Institution $a, Institution $b): int => strcmp($a->getInstitution(), $b->getInstitution()),
        );

        return $institutions;
    }
}

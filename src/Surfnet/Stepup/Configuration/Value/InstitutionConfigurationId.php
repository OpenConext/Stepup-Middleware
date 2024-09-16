<?php

/**
 * Copyright 2016 SURFnet B.V.
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
use Ramsey\Uuid\Uuid;
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class InstitutionConfigurationId implements JsonSerializable, Stringable
{
    public const UUID_NAMESPACE = '09876543-abcd-0987-abcd-098765432109';

    private readonly string $institutionConfigurationId;

    /**
     * @deprecated To be removed in next release; use normalizedFrom method to account for case-(in)sensitivity issues
     */
    public static function from(Institution $institution): self
    {
        return new self((string)Uuid::uuid5(self::UUID_NAMESPACE, $institution->getInstitution()));
    }

    public static function normalizedFrom(Institution $institution): self
    {
        return new self((string)Uuid::uuid5(self::UUID_NAMESPACE, strtolower($institution->getInstitution())));
    }

    public function __construct(string $institutionConfigurationId)
    {
        if (trim($institutionConfigurationId) === '') {
            throw InvalidArgumentException::invalidType(
                'non-empty string',
                'institutionConfigurationId',
                $institutionConfigurationId,
            );
        }

        if (!Uuid::isValid($institutionConfigurationId)) {
            throw InvalidArgumentException::invalidType(
                'UUID',
                'institutionConfigurationId',
                $institutionConfigurationId,
            );
        }

        $this->institutionConfigurationId = $institutionConfigurationId;
    }

    public function equals(InstitutionConfigurationId $otherInstitutionConfigurationId): bool
    {
        return $this->institutionConfigurationId === $otherInstitutionConfigurationId->institutionConfigurationId;
    }

    public function getInstitutionConfigurationId(): string
    {
        return $this->institutionConfigurationId;
    }

    public function __toString(): string
    {
        return $this->institutionConfigurationId;
    }

    public function jsonSerialize(): string
    {
        return $this->institutionConfigurationId;
    }
}

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

use Rhumsaa\Uuid\Uuid;

final class InstitutionConfigurationId
{
    const UUID_NAMESPACE = '09876543-abcd-0987-abcd-098765432109';

    private $institutionConfigurationId;

    /**
     * @param Institution $institution
     * @return InstitutionConfigurationId
     */
    public static function from(Institution $institution)
    {
        return new self(Uuid::uuid5(self::UUID_NAMESPACE, $institution->getInstitution()));
    }

    private function __construct($institutionConfigurationId)
    {
        $this->institutionConfigurationId = $institutionConfigurationId;
    }

    /**
     * @param InstitutionConfigurationId $otherInstitutionConfigurationId
     * @return bool
     */
    public function equals(InstitutionConfigurationId $otherInstitutionConfigurationId)
    {
        return $this->institutionConfigurationId === $otherInstitutionConfigurationId->institutionConfigurationId;
    }

    /**
     * @return string
     */
    public function getInstitutionConfigurationId()
    {
        return $this->institutionConfigurationId;
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }

    public function __toString()
    {
        return $this->institutionConfigurationId;
    }
}

<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;

final class AllowedSecondFactorListUpdatedEvent implements SerializableInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    private $institutionConfigurationId;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var AllowedSecondFactorList
     */
    private $allowedSecondFactorList;

    public function __construct(
        InstitutionConfigurationId $institutionConfigurationId,
        Institution $institution,
        AllowedSecondFactorList $allowedSecondFactorList
    ) {
        $this->institutionConfigurationId = $institutionConfigurationId;
        $this->institution                = $institution;
        $this->allowedSecondFactorList    = $allowedSecondFactorList;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new Institution($data['institution']),
            AllowedSecondFactorList::deserialize($data['allowed_second_factor_list'])
        );
    }

    public function serialize()
    {
        return [
            'institution_configuration_id' => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'institution'                  => $this->institution->getInstitution(),
            'allowed_second_factor_list'  => $this->allowedSecondFactorList->serialize(),
        ];
    }
}

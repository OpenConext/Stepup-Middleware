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

namespace Surfnet\Stepup\Configuration\Event;

use Broadway\Serializer\Serializable as SerializableInterface;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\RaLocationId;

class RaLocationContactInformationChangedEvent implements SerializableInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    public $institutionConfigurationId;

    /**
     * @var RaLocationId
     */
    public $raLocationId;

    /**
     * @var ContactInformation
     */
    public $contactInformation;

    public function __construct(
        InstitutionConfigurationId $institutionConfigurationId,
        RaLocationId $raLocationId,
        ContactInformation $contactInformation
    ) {
        $this->institutionConfigurationId = $institutionConfigurationId;
        $this->raLocationId               = $raLocationId;
        $this->contactInformation         = $contactInformation;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new RaLocationId($data['ra_location_id']),
            new ContactInformation($data['contact_information'])
        );
    }

    public function serialize(): array
    {
        return [
            'institution_configuration_id' => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'ra_location_id'               => $this->raLocationId->getRaLocationId(),
            'contact_information'          => $this->contactInformation->getContactInformation(),
        ];
    }
}

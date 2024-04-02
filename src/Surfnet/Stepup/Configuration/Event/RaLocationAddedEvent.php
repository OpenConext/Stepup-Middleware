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
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocationAddedEvent implements SerializableInterface
{
    public function __construct(
        public InstitutionConfigurationId $institutionConfigurationId,
        public Institution $institution,
        public RaLocationId $raLocationId,
        public RaLocationName $raLocationName,
        public Location $location,
        public ContactInformation $contactInformation
    ) {
    }

    public static function deserialize(array $data): self
    {
        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new Institution($data['institution']),
            new RaLocationId($data['ra_location_id']),
            new RaLocationName($data['ra_location_name']),
            new Location($data['location']),
            new ContactInformation($data['contact_information']),
        );
    }

    public function serialize(): array
    {
        return [
            'institution_configuration_id' => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'institution' => $this->institution->getInstitution(),
            'ra_location_id' => $this->raLocationId->getRaLocationId(),
            'ra_location_name' => $this->raLocationName->getRaLocationName(),
            'location' => $this->location->getLocation(),
            'contact_information' => $this->contactInformation->getContactInformation(),
        ];
    }
}

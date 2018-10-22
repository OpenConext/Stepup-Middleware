<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\Stepup\Identity\Event;

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;

class RegistrationAuthorityInformationAmendedEvent extends IdentityEvent
{
    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var Location
     */
    public $location;

    /**
     * @var ContactInformation
     */
    public $contactInformation;

    /**
     * @var Institution
     */
    public $raInstitution;

    /**
     * @param IdentityId $identityId
     * @param Institution $institution
     * @param NameId $nameId
     * @param Location $location
     * @param ContactInformation $contactInformation
     * @param Institution $raInstitution
     */
    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        NameId $nameId,
        Location $location,
        ContactInformation $contactInformation,
        Institution $raInstitution
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId = $nameId;
        $this->location = $location;
        $this->contactInformation = $contactInformation;
        $this->raInstitution = $raInstitution;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
            new Location($data['location']),
            new ContactInformation($data['contact_information']),
            new Institution($data['ra_institution'])
        );
    }

    public function serialize()
    {
        return [
            'identity_id'         => (string) $this->identityId,
            'institution'         => (string) $this->identityInstitution,
            'name_id'             => (string) $this->nameId,
            'location'            => (string) $this->location,
            'contact_information' => (string) $this->contactInformation,
            'ra_institution'      => (string) $this->raInstitution,
        ];
    }
}

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
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

/**
 * @deprecated This event is superseded by the IdentityAccreditedAsRaaForInstitutionEvent because an RA institution was needed
 */
class IdentityAccreditedAsRaaEvent extends IdentityEvent implements RightToObtainDataInterface
{
    private array $allowlist = [
        'identity_id',
        'name_id',
        'institution',
        'registration_authority_role',
        'location',
        'contact_information',
    ];

    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var RegistrationAuthorityRole
     */
    public $registrationAuthorityRole;

    /**
     * @var Location
     */
    public $location;

    /**
     * @var ContactInformation
     */
    public $contactInformation;

    /**
     * @param IdentityId $identityId
     * @param NameId $nameId
     * @param Institution $institution
     * @param RegistrationAuthorityRole $role
     * @param Location $location
     * @param ContactInformation $contactInformation
     */
    public function __construct(
        IdentityId $identityId,
        NameId $nameId,
        Institution $institution,
        RegistrationAuthorityRole $role,
        Location $location,
        ContactInformation $contactInformation,
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId = $nameId;
        $this->registrationAuthorityRole = $role;
        $this->location = $location;
        $this->contactInformation = $contactInformation;
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    public static function deserialize(array $data): self
    {
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['institution']),
            RegistrationAuthorityRole::deserialize($data['registration_authority_role']),
            new Location($data['location']),
            new ContactInformation($data['contact_information']),
        );
    }

    public function serialize(): array
    {
        return [
            'identity_id' => (string)$this->identityId,
            'name_id' => (string)$this->nameId,
            'institution' => (string)$this->identityInstitution,
            'registration_authority_role' => $this->registrationAuthorityRole->serialize(),
            'location' => (string)$this->location,
            'contact_information' => (string)$this->contactInformation,
        ];
    }

    public function obtainUserData(): array
    {
        return $this->serialize();
    }

    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}

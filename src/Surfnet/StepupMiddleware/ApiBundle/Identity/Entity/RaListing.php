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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;


#[ORM\Table]
#[ORM\Index(name: 'idx_ra_listing_institution', columns: ['institution'])]
#[ORM\Index(name: 'idx_ra_listing_ra_institution', columns: ['ra_institution'])]
#[ORM\UniqueConstraint(name: 'idx_ra_listing_unique_identity_institution', columns: ['identity_id', 'ra_institution'])]
#[ORM\Entity(repositoryClass: RaListingRepository::class)]
class RaListing implements JsonSerializable
{
    /**
     *
     * @var integer
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /**
     * @var string
     */
    #[ORM\Column(length: 36)]
    public $identityId;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'institution')]
    public $raInstitution;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'institution')]
    public $institution;

    /**
     * @var CommonName
     */
    #[ORM\Column(type: 'stepup_common_name')]
    public $commonName;

    /**
     * @var Email
     */
    #[ORM\Column(type: 'stepup_email')]
    public $email;

    /**
     * @var AuthorityRole
     */
    #[ORM\Column(type: 'authority_role')]
    public $role;

    /**
     * @var Location
     */
    #[ORM\Column(type: 'stepup_location', nullable: true)]
    public $location;

    /**
     * @var ContactInformation
     */
    #[ORM\Column(type: 'stepup_contact_information', nullable: true)]
    public $contactInformation;

    public static function create(
        $identityId,
        Institution $institution,
        CommonName $commonName,
        Email $email,
        AuthorityRole $role,
        Location $location,
        ContactInformation $contactInformation,
        Institution $raInstitution
    ): self {
        if (!is_string($identityId)) {
            throw InvalidArgumentException::invalidType('string', 'id', $identityId);
        }

        $entry                     = new self();
        $entry->identityId         = $identityId;
        $entry->institution        = $institution;
        $entry->commonName         = $commonName;
        $entry->email              = $email;
        $entry->role               = $role;
        $entry->location           = $location;
        $entry->contactInformation = $contactInformation;
        $entry->raInstitution      = $raInstitution;

        return $entry;
    }

    public function jsonSerialize()
    {
        return [
            'identity_id'         => $this->identityId,
            'institution'         => (string) $this->institution,
            'ra_institution'      => (string) $this->raInstitution,
            'common_name'         => (string) $this->commonName,
            'email'               => (string) $this->email,
            'role'                => (string) $this->role,
            'location'            => (string) $this->location,
            'contact_information' => (string) $this->contactInformation,
        ];
    }
}

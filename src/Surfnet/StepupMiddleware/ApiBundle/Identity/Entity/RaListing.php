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
use Surfnet\Stepup\IdentifyingData\Value\CommonName;
use Surfnet\Stepup\IdentifyingData\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_ra_listing_institution", columns={"institution"}),
 *      }
 * )
 */
class RaListing implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $identityId;

    /**
     * @ORM\Column(type="institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_common_name")
     *
     * @var CommonName
     */
    public $commonName;

    /**
     * @ORM\Column(type="stepup_email")
     *
     * @var Email
     */
    public $email;

    /**
     * @ORM\Column(length=20)
     *
     * @var string
     */
    public $role;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    public $location;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    public $contactInformation;

    public static function create(
        $identityId,
        Institution $institution,
        CommonName $commonName,
        Email $email,
        $role,
        $location,
        $contactInformation
    ) {
        if (!is_string($identityId)) {
            throw InvalidArgumentException::invalidType('string', 'id', $identityId);
        }

        if (!is_string($role)) {
            throw InvalidArgumentException::invalidType('string', 'role', $role);
        }

        if (!is_string($location)) {
            throw InvalidArgumentException::invalidType('string', 'location', $location);
        }

        if (!is_string($contactInformation)) {
            throw InvalidArgumentException::invalidType('string', 'contactInformation', $contactInformation);
        }

        $entry                     = new self();
        $entry->identityId         = $identityId;
        $entry->institution        = $institution;
        $entry->commonName         = $commonName;
        $entry->email              = $email;
        $entry->role               = $role;
        $entry->location           = $location;
        $entry->contactInformation = $contactInformation;

        return $entry;
    }

    public function jsonSerialize()
    {
        return [
            'identity_id'         => $this->identityId,
            'institution'         => (string) $this->institution,
            'common_name'         => (string) $this->commonName,
            'email'               => (string) $this->email,
            'role'                => $this->role,
            'location'            => $this->location,
            'contact_information' => $this->contactInformation
        ];
    }
}

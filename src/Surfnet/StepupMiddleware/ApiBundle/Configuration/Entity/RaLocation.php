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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity;

use JsonSerializable;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *      repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\RaLocationRepository"
 * )
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_ra_location_institution", columns={"institution"})
 *     }
 * )
 */
class RaLocation implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $raLocationId;

    /**
     * @ORM\Column(type="stepup_configuration_location")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_ra_location_name")
     *
     * @var RaLocationName
     */
    public $raLocationName;

    /**
     * @ORM\Column(type="stepup_configuration_location")
     *
     * @var Location
     */
    public $location;

    /**
     * @ORM\Column(type="stepup_configuration_contact_information")
     *
     * @var ContactInformation
     */
    public $contactInformation;

    public static function create(
        $raLocationId,
        Institution $institution,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if (!is_string($raLocationId)) {
            throw InvalidArgumentException::invalidType('string', 'raLocationId', $raLocationId);
        }

        $raLocation = new self;

        $raLocation->raLocationId               = $raLocationId;
        $raLocation->institution                = $institution;
        $raLocation->raLocationName             = $raLocationName;
        $raLocation->location                   = $location;
        $raLocation->contactInformation         = $contactInformation;

        return $raLocation;
    }

    public function jsonSerialize()
    {
        return [
            'ra_location_id'      => $this->raLocationId,
            'institution'         => $this->institution,
            'ra_location_name'    => $this->raLocationName,
            'location'            => $this->location,
            'contact_information' => $this->contactInformation,
        ];
    }
}

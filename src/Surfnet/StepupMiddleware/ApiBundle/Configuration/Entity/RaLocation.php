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
    public $id;

    /**
     * @ORM\Column(type="stepup_configuration_institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_ra_location_name")
     *
     * @var RaLocationName
     */
    public $name;

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
        $id,
        Institution $institution,
        RaLocationName $name,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if (!is_string($id)) {
            throw InvalidArgumentException::invalidType('string', 'id', $id);
        }

        $raLocation = new self;

        $raLocation->id                 = $id;
        $raLocation->institution        = $institution;
        $raLocation->name               = $name;
        $raLocation->location           = $location;
        $raLocation->contactInformation = $contactInformation;

        return $raLocation;
    }

    public function jsonSerialize()
    {
        return [
            'id'                  => $this->id,
            'institution'         => $this->institution,
            'name'                => $this->name,
            'location'            => $this->location,
            'contact_information' => $this->contactInformation,
        ];
    }
}

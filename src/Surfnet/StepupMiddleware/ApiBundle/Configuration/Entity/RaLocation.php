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

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\RaLocationRepository;

#[ORM\Table]
#[ORM\Index(name: 'idx_ra_location_institution', columns: ['institution'])]
#[ORM\Entity(repositoryClass: RaLocationRepository::class)]
class RaLocation implements JsonSerializable
{
    /**
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'stepup_configuration_institution')]
    public Institution $institution;

    /**
     * @var RaLocationName
     */
    #[ORM\Column(type: 'stepup_ra_location_name')]
    public RaLocationName $name;

    /**
     * @var Location
     */
    #[ORM\Column(type: 'stepup_configuration_location')]
    public Location $location;

    /**
     * @var ContactInformation
     */
    #[ORM\Column(type: 'stepup_configuration_contact_information')]
    public ContactInformation $contactInformation;

    public static function create(
        string $id,
        Institution $institution,
        RaLocationName $name,
        Location $location,
        ContactInformation $contactInformation,
    ): self {
        $raLocation = new self;

        $raLocation->id = $id;
        $raLocation->institution = $institution;
        $raLocation->name = $name;
        $raLocation->location = $location;
        $raLocation->contactInformation = $contactInformation;

        return $raLocation;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'institution' => $this->institution,
            'name' => $this->name,
            'location' => $this->location,
            'contact_information' => $this->contactInformation,
        ];
    }
}

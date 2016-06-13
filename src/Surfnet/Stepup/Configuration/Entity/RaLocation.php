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

namespace Surfnet\Stepup\Configuration\Entity;

use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocation
{
    /**
     * @var RaLocationId
     */
    private $raLocationId;

    /**
     * @var RaLocationName
     */
    private $locationName;

    /**
     * @var Location
     */
    private $location;

    /**
     * @var ContactInformation
     */
    private $contactInformation;

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     * @return RaLocation
     */
    public static function create(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        return new self($raLocationId, $raLocationName, $location, $contactInformation);
    }

    private function __construct(
        RaLocationId $raLocationId,
        RaLocationName $locationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $this->raLocationId       = $raLocationId;
        $this->locationName       = $locationName;
        $this->location           = $location;
        $this->contactInformation = $contactInformation;
    }

    public function hasRaLocationId(RaLocationId $raLocationId)
    {
        return $this->raLocationId->equals($raLocationId);
    }

    /**
     * @return RaLocationId
     */
    public function getRaLocationId()
    {
        return $this->raLocationId;
    }

    /**
     * @return RaLocationName
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return ContactInformation
     */
    public function getContactInformation()
    {
        return $this->contactInformation;
    }
}

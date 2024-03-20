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
    public static function create(
        RaLocationId $id,
        RaLocationName $name,
        Location $location,
        ContactInformation $contactInformation,
    ): self {
        return new self($id, $name, $location, $contactInformation);
    }

    private function __construct(
        private readonly RaLocationId $id,
        private RaLocationName $name,
        private Location $location,
        private ContactInformation $contactInformation,
    ) {
    }

    public function rename(RaLocationName $name): void
    {
        $this->name = $name;
    }

    public function relocate(Location $location): void
    {
        $this->location = $location;
    }

    public function changeContactInformation(ContactInformation $contactInformation): void
    {
        $this->contactInformation = $contactInformation;
    }

    public function hasId(RaLocationId $otherId): bool
    {
        return $this->id->equals($otherId);
    }

    /**
     * @return RaLocationId
     */
    public function getId(): RaLocationId
    {
        return $this->id;
    }

    /**
     * @return RaLocationName
     */
    public function getName(): RaLocationName
    {
        return $this->name;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @return ContactInformation
     */
    public function getContactInformation(): ContactInformation
    {
        return $this->contactInformation;
    }
}

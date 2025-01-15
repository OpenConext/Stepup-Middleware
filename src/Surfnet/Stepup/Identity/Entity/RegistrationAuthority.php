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

namespace Surfnet\Stepup\Identity\Entity;

use Broadway\EventSourcing\SimpleEventSourcedEntity;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
final class RegistrationAuthority extends SimpleEventSourcedEntity
{
    private ?RegistrationAuthorityRole $role = null;

    // @phpstan-ignore-next-line PHPStan can not see that this field is written when serialized to the database
    private ?Location $location = null;

    // @phpstan-ignore-next-line PHPStan can not see that this field is written when serialized to the database
    private ?ContactInformation $contactInformation = null;

    // @phpstan-ignore-next-line PHPStan can not see that this field is written when serialized to the database
    private ?Institution $institution = null;

    public static function accreditWith(
        RegistrationAuthorityRole $role,
        Location $location,
        ContactInformation $contactInformation,
        Institution $institution,
    ): self {
        $registrationAuthority = new self();
        $registrationAuthority->role = $role;
        $registrationAuthority->location = $location;
        $registrationAuthority->contactInformation = $contactInformation;
        $registrationAuthority->institution = $institution;

        return $registrationAuthority;
    }

    public function amendInformation(Location $location, ContactInformation $contactInformation): void
    {
        $this->location = $location;
        $this->contactInformation = $contactInformation;
    }

    public function appointAs(RegistrationAuthorityRole $role): void
    {
        $this->role = $role;
    }

    public function isAppointedAs(RegistrationAuthorityRole $role): bool
    {
        return $this->role->equals($role);
    }

    public function getRole(): ?RegistrationAuthorityRole
    {
        return $this->role;
    }
}

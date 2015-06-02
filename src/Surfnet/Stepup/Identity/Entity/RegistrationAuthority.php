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

use Broadway\EventSourcing\EventSourcedEntity;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
final class RegistrationAuthority extends EventSourcedEntity
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole
     */
    private $role;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Location
     */
    private $location;

    /**
     * @var \Surfnet\Stepup\Identity\Value\ContactInformation
     */
    private $contactInformation;

    /**
     * @param RegistrationAuthorityRole $role
     * @param Location                  $location
     * @param ContactInformation        $contactInformation
     * @return RegistrationAuthority
     */
    public static function accreditWith(
        RegistrationAuthorityRole $role,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $registrationAuthority                     = new self();
        $registrationAuthority->role               = $role;
        $registrationAuthority->location           = $location;
        $registrationAuthority->contactInformation = $contactInformation;

        return $registrationAuthority;
    }

    /**
     * @param Location           $location
     * @param ContactInformation $contactInformation
     */
    public function amendInformation(Location $location, ContactInformation $contactInformation)
    {
        $this->location = $location;
        $this->contactInformation = $contactInformation;
    }

    /**
     * @param RegistrationAuthorityRole $role
     * @return void
     */
    public function appointAs(RegistrationAuthorityRole $role)
    {
        $this->role = $role;
    }

    /**
     * @param RegistrationAuthorityRole $role
     * @return bool
     */
    public function isAppointedAs(RegistrationAuthorityRole $role)
    {
        return $this->role->equals($role);
    }

    /**
     * @return RegistrationAuthorityRole
     */
    public function getRole()
    {
        return $this->role;
    }
}

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
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_raa_institution", columns={"institution"}),
 *          @ORM\Index(name="idx_raa_institution_nameid", columns={"institution", "name_id"}),
 *      }
 * )
 */
class Raa implements JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(type="institution", nullable=false)
     */
    public $institution;

    /**
     * @var string
     *
     * @ORM\Column(type="stepup_name_id", nullable=false)
     */
    public $nameId;

    /**
     * @var string
     *
     * @ORM\Column(type="stepup_location", nullable=true)
     */
    public $location;

    /**
     * @var string
     *
     * @ORM\Column(type="stepup_contact_information", nullable=true)
     */
    public $contactInformation;

    private function __construct(
        Institution $institution,
        NameId $nameId,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $this->id                 = (string) Uuid::uuid4();
        $this->institution        = $institution;
        $this->nameId             = $nameId;
        $this->location           = $location;
        $this->contactInformation = $contactInformation;
    }

    /**
     * @param Institution        $institution
     * @param NameId             $nameId
     * @param Location           $location
     * @param ContactInformation $contactInformation
     * @return Raa
     */
    public static function create(
        Institution $institution,
        NameId $nameId,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $raa = new self($institution, $nameId, $location, $contactInformation);

        return $raa;
    }

    public function jsonSerialize()
    {
        return [
            'name_id'             => $this->nameId,
            'institution'         => $this->institution,
            'location'            => $this->location,
            'contact_information' => $this->contactInformation,
        ];
    }
}

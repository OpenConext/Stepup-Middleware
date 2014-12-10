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
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_ra_institution", columns={"institution"}),
 *          @ORM\Index(name="idx_ra_institution_nameid", columns={"institution", "name_id"}),
 *      }
 * )
 */
class Ra implements JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    public $institution;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    public $nameId;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    public $location;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    public $contactInformation;

    private function __construct($institution, $nameId)
    {
        $this->institution = $institution;
        $this->nameId      = $nameId;
    }

    /**
     * @param string $institution
     * @param string $nameId
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Raa
     */
    public static function create($institution, $nameId)
    {
        if (!is_string($institution)) {
            throw InvalidArgumentException::invalidType('string', 'institution', $institution);
        }

        if (!is_string($nameId)) {
            throw InvalidArgumentException::invalidType('string', 'nameId', $nameId);
        }

        $raa = new self($institution, $nameId);

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

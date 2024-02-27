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
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

/**
 * A second factor as displayed in the registration authority application. One exists for every second factor,
 * regardless of state. As such, it sports a status property, indicating whether its vetted, revoked etc.
 */
#[ORM\Table]
#[ORM\Index(name: 'idx_ra_second_factor_second_factor_id', columns: ['second_factor_id'])]
#[ORM\Index(name: 'idx_ra_second_factor_identity_id', columns: ['identity_id'])]
#[ORM\Index(name: 'idx_ra_second_factor_institution', columns: ['institution'])]
#[ORM\Index(name: 'idx_ra_second_factor_name', columns: ['name'], flags: ['FULLTEXT'])]
#[ORM\Index(name: 'idx_ra_second_factor_email', columns: ['email'], flags: ['FULLTEXT'])]
#[ORM\Entity(repositoryClass: RaSecondFactorRepository::class)]
class RaSecondFactor implements JsonSerializable
{
    /**
     *
     * @var string The second factor's ID (UUID).
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public $id;

    /**
     * @var string
     */
    #[ORM\Column(length: 16)]
    public $type;

    /**
     * @var string The ID of the specific instance of second factor type (ie. phone number, Yubikey public ID).
     */
    #[ORM\Column(length: 255)]
    public $secondFactorId;

    /**
     * @var string
     */
    #[ORM\Column(length: 36)]
    public $identityId;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'institution')]
    public $institution;

    /**
     * The name of the registrant.
     *
     *
     * @var CommonName
     */
    #[ORM\Column(type: 'stepup_common_name')]
    public $name;

    /**
     * The e-mail of the registrant.
     *
     *
     * @var Email
     */
    #[ORM\Column(type: 'stepup_email')]
    public $email;

    /**
     * @var DocumentNumber
     */
    #[ORM\Column(type: 'stepup_document_number', nullable: true)]
    public $documentNumber;

    /**
     * @var SecondFactorStatus
     */
    #[ORM\Column(type: 'stepup_second_factor_status')]
    public $status;

    /**
     * @param string $id
     * @param string $type
     * @param string $secondFactorId
     * @param string $identityId
     * @param Institution $institution
     * @param CommonName $name
     * @param Email $email
     * @param DocumentNumber|null $documentNumber
     */
    public function __construct(
        $id,
        $type,
        $secondFactorId,
        $identityId,
        Institution $institution,
        CommonName $name,
        Email $email,
        DocumentNumber $documentNumber = null
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->secondFactorId = $secondFactorId;
        $this->identityId = $identityId;
        $this->institution = $institution;
        $this->name = $name;
        $this->email = $email;
        $this->documentNumber = $documentNumber;
        $this->status = SecondFactorStatus::unverified();
    }

    public function jsonSerialize()
    {
        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'second_factor_id' => $this->secondFactorId,
            'status'           => (string) $this->status,
            'identity_id'      => $this->identityId,
            'name'             => $this->name,
            'document_number'  => $this->documentNumber,
            'email'            => $this->email,
            'institution'      => $this->institution,
        ];
    }
}

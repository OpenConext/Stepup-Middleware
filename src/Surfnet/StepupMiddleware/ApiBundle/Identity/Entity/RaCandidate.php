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
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;

/**
 * Be aware that this entity is used for the RA Candidate presentation only. This entity shouldn't be used to store any RA candidates.
 */
#[ORM\Entity(repositoryClass: RaCandidateRepository::class, readOnly: true)]
class RaCandidate implements JsonSerializable
{
    /**
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public $identityId;

    /**
     *
     * @var Institution
     */
    #[ORM\Id]
    #[ORM\Column(type: 'institution')]
    public $raInstitution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    #[ORM\Column(type: 'institution')]
    public $institution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\NameId
     */
    #[ORM\Column(type: 'stepup_name_id')]
    public $nameId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    #[ORM\Column(type: 'stepup_common_name')]
    public $commonName;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    #[ORM\Column(type: 'stepup_email')]
    public $email;

    private function __construct()
    {
    }

    public static function nominate(
        IdentityId $identityId,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Institution $raInstitution
    ) {
        $candidate                = new self();
        $candidate->identityId    = (string) $identityId;
        $candidate->institution   = $institution;
        $candidate->nameId        = $nameId;
        $candidate->commonName    = $commonName;
        $candidate->email         = $email;
        $candidate->raInstitution = $raInstitution;

        return $candidate;
    }

    public function jsonSerialize()
    {
        return [
            'identity_id'    => $this->identityId,
            'institution'    => $this->institution,
            'common_name'    => $this->commonName,
            'email'          => $this->email,
            'name_id'        => $this->nameId,
            'ra_institution' => $this->raInstitution,
        ];
    }
}

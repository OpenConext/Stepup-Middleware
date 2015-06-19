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

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_ra_candidate_institution", columns={"institution"}),
 *          @ORM\Index(name="idx_ra_candidate_name_id", columns={"name_id"}),
 *          @ORM\Index(name="idxft_ra_candidate_email", columns={"email"}, flags={"FULLTEXT"}),
 *          @ORM\Index(name="idxft_ra_candidate_commonname", columns={"common_name"}, flags={"FULLTEXT"})
 *      }
 * )
 */
class RaCandidate implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $identityId;

    /**
     * @ORM\Column(type="institution")
     *
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_name_id")
     *
     * @var \Surfnet\Stepup\Identity\Value\NameId
     */
    public $nameId;

    /**
     * @ORM\Column(type="stepup_common_name")
     *
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    public $commonName;

    /**
     * @ORM\Column(type="stepup_email")
     *
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    public $email;

    public static function nominate(
        IdentityId $identityId,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email
    ) {
        $candidate              = new self();
        $candidate->identityId  = (string) $identityId;
        $candidate->institution = $institution;
        $candidate->nameId      = $nameId;
        $candidate->commonName  = $commonName;
        $candidate->email       = $email;

        return $candidate;
    }

    public function jsonSerialize()
    {
        return [
            'identity_id' => $this->identityId,
            'institution' => $this->institution,
            'common_name' => $this->commonName,
            'email'       => $this->email,
            'name_id'     => $this->nameId
        ];
    }
}

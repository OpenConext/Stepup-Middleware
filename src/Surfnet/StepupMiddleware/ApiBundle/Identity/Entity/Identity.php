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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_identity_institution", columns={"institution"}),
 *          @ORM\Index(name="idxft_identity_email", columns={"email"}, flags={"FULLTEXT"}),
 *          @ORM\Index(name="idxft_identity_commonname", columns={"common_name"}, flags={"FULLTEXT"})
 *      }
 * )
 */
class Identity implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $nameId;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $commonName;

    /**
     * @ORM\Column(type="institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $email;

    /**
     * A list of all second factors, whose e-mails haven't been verified.
     *
     * @ORM\OneToMany(targetEntity="UnverifiedSecondFactor", mappedBy="identity", cascade={"persist"})
     *
     * @var Collection|UnverifiedSecondFactor[]
     */
    public $unverifiedSecondFactors;

    /**
     * A list of all second factors, whose e-mails have been verified and are awaiting vetting by an RA.
     *
     * @ORM\OneToMany(targetEntity="VerifiedSecondFactor", mappedBy="identity", cascade={"persist"})
     *
     * @var Collection|VerifiedSecondFactor[]
     */
    public $verifiedSecondFactors;

    public static function create(
        $id,
        Institution $institution,
        $nameId,
        $email,
        $commonName
    ) {
        if (!is_string($id)) {
            throw InvalidArgumentException::invalidType('string', 'id', $id);
        }

        if (!is_string($nameId)) {
            throw InvalidArgumentException::invalidType('string', 'nameId', $nameId);
        }

        if (!is_string($email)) {
            throw InvalidArgumentException::invalidType('string', 'email', $email);
        }

        if (!is_string($commonName)) {
            throw InvalidArgumentException::invalidType('string', 'commonName', $commonName);
        }

        $identity = new self();

        $identity->id = $id;
        $identity->nameId = $nameId;
        $identity->institution = $institution;
        $identity->email = $email;
        $identity->commonName = $commonName;
        $identity->unverifiedSecondFactors = new ArrayCollection();
        $identity->verifiedSecondFactors = new ArrayCollection();

        return $identity;
    }

    public function jsonSerialize()
    {
        return [
            'id'                        => $this->id,
            'name_id'                   => $this->nameId,
            'institution'               => (string) $this->institution,
            'email'                     => $this->email,
            'common_name'               => $this->commonName,
        ];
    }
}

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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;

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
     * @ORM\Column(type="institution")
     *
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_email")
     *
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    public $email;

    /**
     * @ORM\Column(type="stepup_locale")
     *
     * @var \Surfnet\Stepup\Identity\Value\Locale
     */
    public $preferredLocale;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * Indicator if the first vetted second factor was of the self-asserted token type
     *
     * Three possible values:
     * - null: Identity does not have a vetted second factor yet
     * - true: The Identity has registered a self-asserted second factor token
     * - false: The first token was not self-asserted but one of the other vetting types
     *
     * @var mixed null|bool
     */
    public $possessedSelfAssertedToken;

    public static function create(
        string $id,
        Institution $institution,
        NameId $nameId,
        Email $email,
        CommonName $commonName,
        Locale $preferredLocale
    ) {
        $identity = new self();

        $identity->id = $id;
        $identity->nameId = $nameId;
        $identity->institution = $institution;
        $identity->email = $email;
        $identity->commonName = $commonName;
        $identity->preferredLocale = $preferredLocale;
        return $identity;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name_id' => $this->nameId,
            'institution' => $this->institution,
            'email' => $this->email,
            'common_name' => $this->commonName,
            'preferred_locale' => $this->preferredLocale,
        ];
    }
}

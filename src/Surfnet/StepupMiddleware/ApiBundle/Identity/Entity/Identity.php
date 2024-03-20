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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

#[ORM\Table]
#[ORM\Index(name: 'idx_identity_institution', columns: ['institution'])]
#[ORM\Index(name: 'idxft_identity_email', columns: ['email'], flags: ['FULLTEXT'])]
#[ORM\Index(name: 'idxft_identity_commonname', columns: ['common_name'], flags: ['FULLTEXT'])]
#[ORM\Entity(repositoryClass: IdentityRepository::class)]
class Identity implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    #[ORM\Column(type: 'stepup_name_id')]
    public NameId $nameId;

    #[ORM\Column(type: 'stepup_common_name')]
    public CommonName $commonName;

    #[ORM\Column(type: 'institution')]
    public Institution $institution;

    #[ORM\Column(type: 'stepup_email')]
    public Email $email;

    #[ORM\Column(type: 'stepup_locale')]
    public Locale $preferredLocale;
    public ?bool $possessedSelfAssertedToken;

    public static function create(
        string $id,
        Institution $institution,
        NameId $nameId,
        Email $email,
        CommonName $commonName,
        Locale $preferredLocale,
    ): self {
        $identity = new self();

        $identity->id = $id;
        $identity->nameId = $nameId;
        $identity->institution = $institution;
        $identity->email = $email;
        $identity->commonName = $commonName;
        $identity->preferredLocale = $preferredLocale;
        return $identity;
    }

    public function jsonSerialize(): array
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

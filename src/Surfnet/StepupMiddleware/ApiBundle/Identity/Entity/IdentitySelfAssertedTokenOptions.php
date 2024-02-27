<?php

/**
 * Copyright 2022 SURFnet bv
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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentitySelfAssertedTokenOptionsRepository;

#[ORM\Entity(repositoryClass: IdentitySelfAssertedTokenOptionsRepository::class)]
class IdentitySelfAssertedTokenOptions implements JsonSerializable
{
    /**
     *
     * @var IdentityId
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public $identityId;

    /**
     *  In order to determine if the user is allowed to register
     *  a self-asserted token. One of the conditions is that there should
     *  be no previous token registration in his name. Regardless of type.
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    public $possessedToken = false;

    /**
     *  Indicator if Identity is allowed to work with Recovery Tokens
     *
     *  Satisfies business rule:
     *  Limit a user to only add/modify/see recovery methods in the overview
     *  screen when they have previously had an active self-asserted token
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    public $possessedSelfAssertedToken;

    public static function create(
        IdentityId $identityId,
        bool $possessedToken,
        bool $possessedSelfAssertedToken,
    ): self {
        $identitySelfAssertedTokenOptions = new self();

        $identitySelfAssertedTokenOptions->identityId = $identityId;
        $identitySelfAssertedTokenOptions->possessedToken = $possessedToken;
        $identitySelfAssertedTokenOptions->possessedSelfAssertedToken = $possessedSelfAssertedToken;
        return $identitySelfAssertedTokenOptions;
    }

    public function jsonSerialize()
    {
        return [
            'identity_id' => (string)$this->identityId,
            'possessed_self_asserted_token' => $this->possessedSelfAssertedToken,
            'possessed_token' => $this->possessedToken,
        ];
    }
}

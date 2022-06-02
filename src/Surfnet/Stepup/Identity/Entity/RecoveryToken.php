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

namespace Surfnet\Stepup\Identity\Entity;

use Broadway\EventSourcing\SimpleEventSourcedEntity;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;

final class RecoveryToken extends SimpleEventSourcedEntity
{
    /**
     * @var RecoveryTokenId
     */
    private $tokenId;

    /**
     * @var RecoveryTokenType
     */
    private $type;

    public static function create(
        RecoveryTokenId $id,
        RecoveryTokenType $type
    ): self {
        $token = new self;
        $token->tokenId = $id;
        $token->type = $type;
        return $token;
    }

    public function getTokenId(): RecoveryTokenId
    {
        return $this->tokenId;
    }

    public function getType(): RecoveryTokenType
    {
        return $this->type;
    }
}

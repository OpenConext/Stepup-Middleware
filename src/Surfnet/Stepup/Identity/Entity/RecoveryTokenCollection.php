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

use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use function array_key_exists;

final class RecoveryTokenCollection
{
    /**
     * @var RecoveryToken[]
     */
    private $recoveryTokens = [];

    public function set(RecoveryToken $recoveryToken): void
    {
        $this->recoveryTokens[(string)$recoveryToken->getTokenId()] = $recoveryToken;
    }

    public function get(RecoveryTokenId $id): RecoveryToken
    {
        if (!array_key_exists((string)$id, $this->recoveryTokens)) {
            throw new DomainException(sprintf('Unable to find recovery token with id %s', $id));
        }
        return $this->recoveryTokens[(string)$id];
    }

    public function hasType(RecoveryTokenType $type)
    {
        foreach ($this->recoveryTokens as $token) {
            if ($type->equals($token->getType())) {
                return true;
            }
        }
        return false;
    }

    public function count(): int
    {
        return count($this->recoveryTokens);
    }

    public function remove(RecoveryTokenId $recoveryTokenId)
    {
        unset($this->recoveryTokens[(string)$recoveryTokenId]);
    }
}

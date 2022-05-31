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

use Surfnet\Stepup\Identity\Value\RecoveryTokenId;

final class RecoveryTokenCollection
{
    /**
     * @var RecoveryToken[]
     */
    private $recoveryTokens = [];

    public function set(RecoveryToken $recoveryToken): void
    {
        $this->recoveryTokens[] = $recoveryToken;
    }

    public function get(RecoveryTokenId $id): RecoveryToken
    {
        return $this->recoveryTokens[(string)$id];
    }

    public function count(): int
    {
        return count($this->recoveryTokens);
    }
}

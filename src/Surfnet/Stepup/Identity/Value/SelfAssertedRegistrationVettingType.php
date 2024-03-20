<?php

/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\Stepup\Identity\Value;

class SelfAssertedRegistrationVettingType implements VettingType
{
    /**
     * @var string
     */
    protected string $type = VettingType::TYPE_SELF_ASSERTED_REGISTRATION;

    public function __construct(protected RecoveryTokenId $authoringRecoveryToken)
    {
    }

    public static function deserialize(array $data): self
    {
        $recoveryTokenId = new RecoveryTokenId($data['recovery_token_id']);
        return new self($recoveryTokenId);
    }

    public function auditLog(): string
    {
        return sprintf(' (self asserted registration using recovery token: %s)', $this->authoringRecoveryToken);
    }

    public function authoringRecoveryToken(): RecoveryTokenId
    {
        return $this->authoringRecoveryToken;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type(),
            'recovery_token_id' => (string)$this->authoringRecoveryToken,
        ];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type();
    }

    public function getDocumentNumber(): ?DocumentNumber
    {
        return null;
    }
}

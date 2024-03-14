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

namespace Surfnet\Stepup\Identity\Event;

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

class RecoveryTokenRevokedEvent extends IdentityEvent implements RightToObtainDataInterface
{
    /**
     * @var RecoveryTokenId
     */
    public $recoveryTokenId;
    /**
     * @var RecoveryTokenType
     */
    public $recoveryTokenType;

    private array $allowlist = [
        'identity_id',
        'identity_institution',
        'recovery_token_id',
        'recovery_token_type',
    ];

    final public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        RecoveryTokenId $recoveryTokenId,
        RecoveryTokenType $recoveryTokenType,
    ) {
        parent::__construct($identityId, $identityInstitution);
        $this->recoveryTokenId = $recoveryTokenId;
        $this->recoveryTokenType = $recoveryTokenType;
    }

    final public static function deserialize(array $data): self
    {
        $recoveryTokenType = new RecoveryTokenType($data['recovery_token_type']);

        return new static(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new RecoveryTokenId($data['recovery_token_id']),
            $recoveryTokenType,
        );
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->recoveryTokenId = $this->recoveryTokenId;
        $metadata->recoveryTokenType = $this->recoveryTokenType;

        return $metadata;
    }

    public function obtainUserData(): array
    {
        return $this->serialize();
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    final public function serialize(): array
    {
        return [
            'identity_id' => (string)$this->identityId,
            'identity_institution' => (string)$this->identityInstitution,
            'recovery_token_id' => (string)$this->recoveryTokenId,
            'recovery_token_type' => (string)$this->recoveryTokenType,
        ];
    }

    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}

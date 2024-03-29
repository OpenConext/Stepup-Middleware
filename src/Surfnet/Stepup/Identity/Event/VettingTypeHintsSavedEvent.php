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
use Surfnet\Stepup\Identity\Collection\VettingTypeHintCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

class VettingTypeHintsSavedEvent extends IdentityEvent implements RightToObtainDataInterface
{
    private $allowlist = [
        'identity_id',
        'identity_institution',
        'hints',
        'institution'
    ];

    /**
     * @var VettingTypeHintCollection
     */
    public $hints;

    /**
     * @var Institution
     */
    public $institution;

    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        VettingTypeHintCollection $hints,
        Institution $institution
    ) {
        parent::__construct($identityId, $identityInstitution);
        $this->hints = $hints;
        $this->institution = $institution;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    public function obtainUserData(): array
    {
        return $this->serialize();
    }

    public function getAllowlist(): array
    {
        return $this->allowlist;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            VettingTypeHintCollection::deserialize($data['hints']),
            new Institution($data['institution'])
        );
    }
    public function serialize(): array
    {
        return [
            'identity_id' => (string) $this->identityId,
            'identity_institution' => (string) $this->identityInstitution,
            'hints' => $this->hints->serialize(),
            'institution' => (string) $this->institution,
        ];
    }
}

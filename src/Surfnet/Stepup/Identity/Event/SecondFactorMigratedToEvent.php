<?php

/**
 * Copyright 2021 SURFnet bv
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
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecondFactorMigratedToEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    /**
     * @var string[]
     */
    private array $allowlist = [
        'identity_id',
        'identity_institution',
        'second_factor_id',
        'target_institution',
        'target_second_factor_id',
        'second_factor_type',
        'second_factor_identifier',
    ];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        public Institution $targetInstitution,
        public SecondFactorId $secondFactorId,
        public SecondFactorId $targetSecondFactorId,
        public SecondFactorType $secondFactorType,
        public SecondFactorIdentifier $secondFactorIdentifier,
    ) {
        parent::__construct($identityId, $institution);
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = $this->secondFactorType;
        $metadata->secondFactorIdentifier = $this->secondFactorIdentifier;
        $metadata->raInstitution = $this->targetInstitution;
        return $metadata;
    }

    public static function deserialize(array $data): self
    {
        $secondFactorType = new SecondFactorType($data['second_factor_type']);
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new Institution($data['target_institution']),
            new SecondFactorId($data['second_factor_id']),
            new SecondFactorId($data['target_second_factor_id']),
            $secondFactorType,
            SecondFactorIdentifierFactory::unknownForType($secondFactorType),
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     *
     * @return array<string, mixed>
     */
    public function serialize(): array
    {
        return [
            'identity_id' => (string)$this->identityId,
            'identity_institution' => (string)$this->identityInstitution,
            'second_factor_id' => (string)$this->secondFactorId,
            'target_institution' => (string)$this->targetInstitution,
            'target_second_factor_id' => (string)$this->targetSecondFactorId,
            'second_factor_type' => (string)$this->secondFactorType,
        ];
    }

    public function getSensitiveData(): SensitiveData
    {
        return (new SensitiveData)
            ->withSecondFactorIdentifier($this->secondFactorIdentifier, $this->secondFactorType);
    }

    public function setSensitiveData(SensitiveData $sensitiveData): void
    {
        $this->secondFactorIdentifier = $sensitiveData->getSecondFactorIdentifier();
    }

    public function obtainUserData(): array
    {
        $serializedPublicUserData = $this->serialize();
        $serializedSensitiveUserData = $this->getSensitiveData()->serialize();
        return array_merge($serializedPublicUserData, $serializedSensitiveUserData);
    }

    /**
     * @return string[]
     */
    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}

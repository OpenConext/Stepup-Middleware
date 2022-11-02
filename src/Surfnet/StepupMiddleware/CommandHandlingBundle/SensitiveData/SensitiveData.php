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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData;

use Broadway\Serializer\Serializable as SerializableInterface;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifier;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifierFactory;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\Stepup\Identity\Value\VettingTypeFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;

class SensitiveData implements SerializableInterface
{
    /**
     * @var CommonName|null
     */
    private $commonName;

    /**
     * @var Email|null
     */
    private $email;

    /**
     * @var SecondFactorIdentifier|null
     */
    private $secondFactorIdentifier;

    /**
     * @var SecondFactorType|null
     */
    private $secondFactorType;

    /**
     * @var VettingType
     */
    private $vettingType;

    /**
     * @var RecoveryTokenType
     */
    private $recoveryTokenType;

    /**
     * @var RecoveryTokenIdentifier
     */
    private $recoveryTokenIdentifier;

    /**
     * @param CommonName $commonName
     * @return SensitiveData
     */
    public function withCommonName(CommonName $commonName)
    {
        $clone = clone $this;
        $clone->commonName = $commonName;

        return $clone;
    }

    /**
     * @param Email $email
     * @return SensitiveData
     */
    public function withEmail(Email $email)
    {
        $clone = clone $this;
        $clone->email = $email;

        return $clone;
    }

    /**
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @param SecondFactorType       $secondFactorType
     * @return SensitiveData
     */
    public function withSecondFactorIdentifier(
        SecondFactorIdentifier $secondFactorIdentifier,
        SecondFactorType $secondFactorType
    ) {
        $clone = clone $this;
        $clone->secondFactorType = $secondFactorType;
        $clone->secondFactorIdentifier = $secondFactorIdentifier;

        return $clone;
    }

    public function withRecoveryTokenSecret(
        RecoveryTokenIdentifier $recoveryTokenIdentifier,
        RecoveryTokenType $type
    ): SensitiveData {
        $clone = clone $this;
        $clone->recoveryTokenType = $type;
        $clone->recoveryTokenIdentifier = $recoveryTokenIdentifier;

        return $clone;
    }

    public function withVettingType(VettingType $vettingType): self
    {
        $clone = clone $this;
        $clone->vettingType = $vettingType;

        return $clone;
    }

    /**
     * Returns an instance in which all sensitive data is forgotten.
     *
     * @return SensitiveData
     */
    public function forget()
    {
        $forgotten = new self();
        $forgotten->secondFactorType = $this->secondFactorType;

        return $forgotten;
    }

    /**
     * @return CommonName
     */
    public function getCommonName()
    {
        return $this->commonName ?: CommonName::unknown();
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email ?: Email::unknown();
    }

    /**
     * @return SecondFactorIdentifier
     */
    public function getSecondFactorIdentifier()
    {
        return $this->secondFactorIdentifier ?: SecondFactorIdentifierFactory::unknownForType($this->secondFactorType);
    }

    public function getRecoveryTokenIdentifier(): ?RecoveryTokenIdentifier
    {
        if ($this->recoveryTokenIdentifier) {
            return $this->recoveryTokenIdentifier;
        }
        if ($this->recoveryTokenType) {
            return RecoveryTokenIdentifierFactory::unknownForType($this->recoveryTokenType);
        }
        return null;
    }

    /**
     * @return VettingType
     */
    public function getVettingType()
    {
        return $this->vettingType ?: new UnknownVettingType();
    }

    public static function deserialize(array $data)
    {
        $self = new self;

        if (isset($data['common_name'])) {
            $self->commonName = new CommonName($data['common_name']);
        }

        if (isset($data['email'])) {
            $self->email = new Email($data['email']);
        }

        if (isset($data['second_factor_type'])) {
            $self->secondFactorType = new SecondFactorType($data['second_factor_type']);
        }

        if (isset($data['second_factor_identifier'])) {
            $self->secondFactorIdentifier =
                SecondFactorIdentifierFactory::forType($self->secondFactorType, $data['second_factor_identifier']);
        }
        if (isset($data['recovery_token_type'])) {
            $self->recoveryTokenType = new RecoveryTokenType($data['recovery_token_type']);
        }

        if (isset($data['recovery_token_identifier'])) {
            $self->recoveryTokenIdentifier = RecoveryTokenIdentifierFactory::forType(
                $self->recoveryTokenType,
                $data['recovery_token_identifier']
            );
        }

        if (isset($data['document_number']) || isset($data['vetting_type'])) {
            $self->vettingType = VettingTypeFactory::fromData($data);
        }

        return $self;
    }

    public function serialize(): array
    {
        $vettingType = (!is_null($this->vettingType)) ? $this->vettingType->jsonSerialize() : null;
        return array_filter([
            'common_name'              => $this->commonName,
            'email'                    => $this->email,
            'second_factor_type'       => $this->secondFactorType,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'recovery_token_type' => (string) $this->recoveryTokenType,
            'recovery_token_identifier' => $this->recoveryTokenIdentifier,
            'vetting_type' => $vettingType
        ]);
    }
}

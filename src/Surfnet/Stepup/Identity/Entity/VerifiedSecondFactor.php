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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * A second factor whose possession has been proven by the registrant and the registrant's e-mail address has been
 * verified. The registrant must visit a registration authority next.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VerifiedSecondFactor extends AbstractSecondFactor
{
    /**
     * @var SecondFactorId
     */
    private $id;

    /**
     * @var Identity
     */
    private $identity;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    private $type;

    /**
     * @var string
     */
    private $secondFactorIdentifier;

    /**
     * @var DateTime
     */
    private $registrationRequestedAt;

    /**
     * @var string
     */
    private $registrationCode;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param SecondFactorType $type
     * @param string $secondFactorIdentifier
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     * @return self
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        SecondFactorType $type,
        $secondFactorIdentifier,
        DateTime $registrationRequestedAt,
        $registrationCode
    ) {
        if (!is_string($registrationCode)) {
            throw InvalidArgumentException::invalidType('string', 'registrationCode', $registrationCode);
        }

        $secondFactor = new self;
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->registrationRequestedAt = $registrationRequestedAt;
        $secondFactor->registrationCode = $registrationCode;

        return $secondFactor;
    }

    final private function __construct()
    {
    }

    /**
     * @param string $registrationCode
     * @param string $secondFactorIdentifier
     * @return bool
     */
    public function hasRegistrationCodeAndIdentifier($registrationCode, $secondFactorIdentifier)
    {
        return strcasecmp($registrationCode, $this->registrationCode) === 0
            && $secondFactorIdentifier === $this->secondFactorIdentifier;
    }

    /**
     * @return bool
     */
    public function canBeVettedNow()
    {
        return !DateTime::now()->comesAfter($this->registrationRequestedAt->add(new \DateInterval('P14D')));
    }

    /**
     * @param string $documentNumber
     */
    public function vet($documentNumber)
    {
        $this->apply(
            new SecondFactorVettedEvent(
                new IdentityId($this->identity->getAggregateRootId()),
                $this->identity->getNameId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $documentNumber,
                $this->identity->getCommonName(),
                $this->identity->getEmail(),
                'en_GB'
            )
        );
    }

    public function revoke()
    {
        $this->apply(
            new VerifiedSecondFactorRevokedEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type
            )
        );
    }

    public function complyWithRevocation(IdentityId $authorityId)
    {
        $this->apply(
            new CompliedWithVerifiedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $authorityId
            )
        );
    }

    /**
     * @return VettedSecondFactor
     */
    public function asVetted()
    {
        return VettedSecondFactor::create(
            $this->id,
            $this->identity,
            $this->type,
            $this->secondFactorIdentifier
        );
    }

    protected function getType()
    {
        return $this->type;
    }
}

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

use Broadway\EventSourcing\EventSourcedEntity;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Token\TokenGenerator;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * A second factor whose possession has been proven by the registrant. The registrant must verify his/her e-mail
 * address to verify this second factor.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UnverifiedSecondFactor extends EventSourcedEntity implements SecondFactor
{
    /**
     * @var Identity
     */
    private $identity;

    /**
     * @var SecondFactorId
     */
    private $id;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    private $type;

    /**
     * @var string
     */
    private $secondFactorIdentifier;

    /**
     * @var EmailVerificationWindow;
     */
    private $verificationWindow;

    /**
     * @var string
     */
    private $verificationNonce;

    /**
     * @param SecondFactorId          $id
     * @param Identity                $identity
     * @param SecondFactorType        $type
     * @param string                  $secondFactorIdentifier
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param string                  $verificationNonce
     * @return UnverifiedSecondFactor
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        SecondFactorType $type,
        $secondFactorIdentifier,
        EmailVerificationWindow $emailVerificationWindow,
        $verificationNonce
    ) {
        if (!is_string($verificationNonce)) {
            throw InvalidArgumentException::invalidType('string', 'verificationNonce', $verificationNonce);
        }

        if (empty($verificationNonce)) {
            throw new InvalidArgumentException("'verificationNonce' may not be empty");
        }

        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->verificationWindow = $emailVerificationWindow;
        $secondFactor->verificationNonce = $verificationNonce;

        return $secondFactor;
    }

    final public function __construct()
    {
    }

    /**
     * @return SecondFactorId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $verificationNonce
     * @return bool
     */
    public function hasNonce($verificationNonce)
    {
        return $this->verificationNonce === $verificationNonce;
    }

    /**
     * @return bool
     */
    public function canBeVerifiedNow()
    {
        return $this->verificationWindow->isOpen();
    }

    public function verifyEmail()
    {
        $this->apply(
            new EmailVerifiedEvent(
                new IdentityId($this->identity->getAggregateRootId()),
                $this->identity->getInstitution(),
                $this->id,
                DateTime::now(),
                TokenGenerator::generateHumanReadableToken(8),
                $this->identity->getCommonName(),
                $this->identity->getEmail(),
                'en_GB'
            )
        );
    }

    public function hasEqualOrHigherLoaComparedTo(SecondFactor $comparable)
    {
        return $comparable->hasTypeWithEqualOrLowerLoaComparedTo($this->type);
    }

    public function hasTypeWithEqualOrLowerLoaComparedTo(SecondFactorType $type)
    {
        return $this->type->hasEqualOrLowerLoaComparedTo($type);
    }

    public function revoke()
    {
        $this->apply(new UnverifiedSecondFactorRevokedEvent($this->identity->getId(), $this->id));
    }

    public function complyWithRevocation(IdentityId $authorityId)
    {
        $this->apply(
            new CompliedWithUnverifiedSecondFactorRevocationEvent($this->identity->getId(), $this->id, $authorityId)
        );
    }

    /**
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     * @return VerifiedSecondFactor
     */
    public function asVerified($registrationRequestedAt, $registrationCode)
    {
        return VerifiedSecondFactor::create(
            $this->id,
            $this->identity,
            $this->type,
            $this->secondFactorIdentifier,
            $registrationRequestedAt,
            $registrationCode
        );
    }
}

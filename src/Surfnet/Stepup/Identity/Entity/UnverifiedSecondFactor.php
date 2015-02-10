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
use DateInterval;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Token\TokenGenerator;

/**
 * A second factor whose possession has been proven by the registrant. The registrant must verify his/her e-mail
 * address to verify this second factor.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UnverifiedSecondFactor extends EventSourcedEntity
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
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $secondFactorIdentifier;

    /**
     * @var DateTime
     */
    private $verificationRequestedAt;

    /**
     * @var string
     */
    private $verificationNonce;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param string $type
     * @param string $secondFactorIdentifier
     * @param DateTime $verificationRequestedAt
     * @param string $verificationNonce
     * @return UnverifiedSecondFactor
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        $type,
        $secondFactorIdentifier,
        DateTime $verificationRequestedAt,
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
        $secondFactor->verificationRequestedAt = $verificationRequestedAt;
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
    public function wouldVerifyEmail($verificationNonce)
    {
        return $this->verificationNonce === $verificationNonce
            && !DateTime::now()->comesAfter($this->verificationRequestedAt->add(new DateInterval('P1D')));
    }

    /**
     * @param string $verificationNonce
     */
    public function verifyEmail($verificationNonce)
    {
        if ($this->verificationNonce !== $verificationNonce) {
            throw new DomainException(
                sprintf(
                    "Cannot verify second factor '%s': verification nonce does not match.",
                    (string) $this->id
                )
            );
        }

        if (DateTime::now()->comesAfter($this->verificationRequestedAt->add(new DateInterval('P1D')))) {
            throw new DomainException(
                sprintf(
                    "Cannot verify possession of e-mail for second factor '%s': " .
                    'verification window of one day has closed.',
                    (string) $this->id
                )
            );
        }

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

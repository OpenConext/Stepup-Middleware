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

use Broadway\Domain\DateTime;
use Broadway\EventSourcing\EventSourcedEntity;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Token\Token;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class SecondFactor extends EventSourcedEntity
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
     * @var DateTime
     */
    private $emailVerificationRequestedAt;

    /**
     * @var string|null
     */
    private $emailVerificationCode;

    /**
     * @var string|null
     */
    private $emailVerificationNonce;

    /**
     * @var DateTime
     */
    private $registrationRequestedAt;

    /**
     * @var string|null
     */
    private $registrationCode;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param DateTime $emailVerificationRequestedAt
     * @param string $emailVerificationCode
     * @param string $emailVerificationNonce
     * @return self
     */
    public static function createUnverified(
        SecondFactorId $id,
        Identity $identity,
        DateTime $emailVerificationRequestedAt,
        $emailVerificationCode,
        $emailVerificationNonce
    ) {
        if (!is_string($emailVerificationCode)) {
            throw InvalidArgumentException::invalidType('string', 'emailVerificationCode', $emailVerificationCode);
        }

        if (empty($emailVerificationCode)) {
            throw new InvalidArgumentException("'emailVerificationCode' may not be empty");
        }

        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->emailVerificationRequestedAt = $emailVerificationRequestedAt;
        $secondFactor->emailVerificationCode = $emailVerificationCode;
        $secondFactor->emailVerificationNonce = $emailVerificationNonce;

        return $secondFactor;
    }

    final public function __construct()
    {
    }

    /**
     * @param SecondFactorId $id
     * @return bool
     */
    public function isIdentifiedBy(SecondFactorId $id)
    {
        return $this->id->equals($id);
    }

    /**
     * @param string $verificationCode
     * @param string $verificationNonce
     */
    public function verifyEmail($verificationCode, $verificationNonce)
    {
        if ($this->emailVerificationCode === null || $this->emailVerificationNonce === null) {
            throw new DomainException(sprintf(
                "Cannot verify possession of e-mail for second factor '%s': possession already verified",
                (string) $this->id
            ));
        }

        if (DateTime::now()->comesAfter($this->emailVerificationRequestedAt->add('P1D'))) {
            throw new DomainException(
                sprintf(
                    "Cannot verify possession of e-mail for second factor '%s': " .
                    "verification window of one day has closed.",
                    (string) $this->id
                )
            );
        }

        if (strcasecmp($this->emailVerificationCode, $verificationCode) !== 0) {
            throw new DomainException(
                sprintf(
                    "Cannot verify possession of e-mail second factor '%s': verification code does not match.",
                    (string) $this->id
                )
            );
        }

        if ($this->emailVerificationNonce !== $verificationNonce) {
            throw new DomainException(
                sprintf(
                    "Cannot verify second factor '%s': verification nonce does not match.",
                    (string) $this->id
                )
            );
        }

        $this->apply(
            new EmailVerifiedEvent(
                new IdentityId($this->identity->getAggregateRootId()),
                $this->id,
                DateTime::now(),
                Token::generateHumanToken(8),
                $this->identity->getCommonName(),
                $this->identity->getEmail()
            )
        );
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->emailVerificationCode = null;
        $this->emailVerificationNonce = null;

        $this->registrationRequestedAt = $event->registrationRequestedAt;
        $this->registrationCode = $event->registrationCode;
    }
}

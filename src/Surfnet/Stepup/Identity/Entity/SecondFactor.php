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
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Token\TokenGenerator;

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
     * @param string $emailVerificationNonce
     * @return self
     */
    public static function createUnverified(
        SecondFactorId $id,
        Identity $identity,
        DateTime $emailVerificationRequestedAt,
        $emailVerificationNonce
    ) {
        if (!is_string($emailVerificationNonce)) {
            throw InvalidArgumentException::invalidType('string', 'emailVerificationNonce', $emailVerificationNonce);
        }

        if (empty($emailVerificationNonce)) {
            throw new InvalidArgumentException("'emailVerificationNonce' may not be empty");
        }

        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->emailVerificationRequestedAt = $emailVerificationRequestedAt;
        $secondFactor->emailVerificationNonce = $emailVerificationNonce;

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
     */
    public function verifyEmail($verificationNonce)
    {
        if ($this->emailVerificationNonce === null) {
            throw new DomainException(sprintf(
                "Cannot verify possession of e-mail for second factor '%s': possession already verified",
                (string) $this->id
            ));
        }

        if (DateTime::now()->comesAfter($this->emailVerificationRequestedAt->add('P1D'))) {
            throw new DomainException(
                sprintf(
                    "Cannot verify possession of e-mail for second factor '%s': " .
                    'verification window of one day has closed.',
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
                TokenGenerator::generateHumanReadableToken(8),
                $this->identity->getCommonName(),
                $this->identity->getEmail(),
                'en_GB'
            )
        );
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->emailVerificationNonce = null;

        $this->registrationRequestedAt = $event->registrationRequestedAt;
        $this->registrationCode = $event->registrationCode;
    }
}

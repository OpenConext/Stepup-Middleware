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

namespace Surfnet\Stepup\Identity;

use Broadway\Domain\DateTime;
use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\SecondFactor;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Token\VerificationCode;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Identity extends EventSourcedAggregateRoot implements IdentityApi
{
    /**
     * @var IdentityId
     */
    private $id;

    /**
     * @var NameId
     */
    private $nameId;

    /**
     * @var SecondFactor|null
     */
    private $secondFactor;

    public static function create(IdentityId $id, NameId $nameId)
    {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $nameId));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function provePossessionOfYubikey(SecondFactorId $secondFactorId, YubikeyPublicId $yubikeyPublicId)
    {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new YubikeyPossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $yubikeyPublicId,
                DateTime::now(),
                VerificationCode::generate(8),
                VerificationCode::generateNonce(),
                'Reinier',
                'rkip@ibuildings.nl'
            )
        );
    }

    public function provePossessionOfPhone(SecondFactorId $secondFactorId, PhoneNumber $phoneNumber)
    {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new PhonePossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $phoneNumber,
                DateTime::now(),
                VerificationCode::generate(8),
                VerificationCode::generateNonce(),
                'Reinier',
                'rkip@ibuildings.nl'
            )
        );
    }

    public function verifyEmail(SecondFactorId $secondFactorId, $verificationCode, $verificationNonce)
    {
        if (!is_string($verificationCode)) {
            throw InvalidArgumentException::invalidType('string', 'verificationCode', $verificationCode);
        }

        if (!is_string($verificationNonce)) {
            throw InvalidArgumentException::invalidType('string', 'verificationNonce', $verificationNonce);
        }

        if ($this->secondFactor === null || !$this->secondFactor->isIdentifiedBy($secondFactorId)) {
            throw new DomainException(
                sprintf(
                    "Cannot verify second factor '%s' with given verification code: registrant does not have second " .
                    "factor in possession.",
                    (string) $secondFactorId
                )
            );
        }

        $this->secondFactor->verifyEmail($verificationCode, $verificationNonce);
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->nameId = $event->nameId;
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->secondFactor = SecondFactor::createUnverified(
            $event->secondFactorId,
            $event->emailVerificationRequestedAt,
            $event->emailVerificationCode,
            $event->emailVerificationNonce
        );
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->secondFactor = SecondFactor::createUnverified(
            $event->secondFactorId,
            $event->emailVerificationRequestedAt,
            $event->emailVerificationCode,
            $event->emailVerificationNonce
        );
    }

    public function getAggregateRootId()
    {
        return (string) $this->id;
    }

    protected function getChildEntities()
    {
        return $this->secondFactor ? [$this->secondFactor] : [];
    }

    /**
     * @throws DomainException
     */
    private function assertUserMayAddSecondFactor()
    {
        if ($this->secondFactor !== null) {
            throw new DomainException('User may not have more than one token');
        }
    }
}

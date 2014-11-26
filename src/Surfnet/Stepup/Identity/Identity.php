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

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\SecondFactor;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Token\Token;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Identity extends EventSourcedAggregateRoot implements IdentityApi
{
    /**
     * @var IdentityId
     */
    private $id;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var NameId
     */
    private $nameId;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $commonName;

    /**
     * @var SecondFactor|null
     */
    private $secondFactor;

    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        $email,
        $commonName
    ) {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function rename($commonName)
    {
        if ($commonName === $this->commonName) {
            return;
        }

        $this->apply(new IdentityRenamedEvent($this->id, $this->commonName, $commonName));
    }

    public function changeEmail($email)
    {
        if ($email === $this->email) {
            return;
        }

        $this->apply(new IdentityEmailChangedEvent($this->id, $this->email, $email));
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
                Token::generateNonce(),
                $this->commonName,
                $this->email
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
                Token::generateNonce(),
                $this->commonName,
                $this->email
            )
        );
    }

    public function verifyEmail($verificationNonce)
    {
        if (!is_string($verificationNonce)) {
            throw InvalidArgumentException::invalidType('string', 'verificationNonce', $verificationNonce);
        }

        if ($this->secondFactor === null) {
            throw new DomainException(
                sprintf(
                    "Cannot verify second factor '%s' with given verification nonce: registrant does not have second " .
                    "factor in possession.",
                    (string) $this->secondFactor->getId()
                )
            );
        }

        $this->secondFactor->verifyEmail($verificationNonce);
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->institution = $event->institution;
        $this->nameId = $event->nameId;
        $this->email = $event->email;
        $this->commonName = $event->commonName;
    }

    protected function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $this->commonName = $event->newName;
    }

    protected function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $this->email = $event->newEmail;
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->secondFactor = SecondFactor::createUnverified(
            $event->secondFactorId,
            $this,
            $event->emailVerificationRequestedAt,
            $event->emailVerificationNonce
        );
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->secondFactor = SecondFactor::createUnverified(
            $event->secondFactorId,
            $this,
            $event->emailVerificationRequestedAt,
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

    /**
     * @return string
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}

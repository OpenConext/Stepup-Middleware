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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;

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
     * @var Collection|UnverifiedSecondFactor[]
     */
    private $unverifiedSecondFactors;

    /**
     * @var Collection|VerifiedSecondFactor[]
     */
    private $verifiedSecondFactors;

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
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                'en_GB'
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
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                'en_GB'
            )
        );
    }

    public function verifyEmail($verificationNonce)
    {
        foreach ($this->unverifiedSecondFactors as $secondFactor) {
            if (!$secondFactor->wouldVerifyEmail($verificationNonce)) {
                continue;
            }

            $secondFactor->verifyEmail($verificationNonce);
            return;
        }

        throw new DomainException(
            "Cannot verify second factor: verification nonce does not apply to any unverified second factors or the " .
            "verification window has closed."
        );
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->institution = $event->institution;
        $this->nameId = $event->nameId;
        $this->email = $event->email;
        $this->commonName = $event->commonName;
        $this->unverifiedSecondFactors = new ArrayCollection();
        $this->verifiedSecondFactors = new ArrayCollection();
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
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            $event->emailVerificationRequestedAt,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            $event->emailVerificationRequestedAt,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->add($secondFactor);
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $this->unverifiedSecondFactors->remove((string) $event->secondFactorId);

        $this->verifiedSecondFactors->set(
            (string) $event->secondFactorId,
            VerifiedSecondFactor::create(
                $event->secondFactorId,
                $this,
                $event->registrationRequestedAt,
                $event->registrationCode
            )
        );
    }

    public function getAggregateRootId()
    {
        return (string) $this->id;
    }

    protected function getChildEntities()
    {
        return array_merge($this->unverifiedSecondFactors->getValues(), $this->verifiedSecondFactors->getValues());
    }

    /**
     * @throws DomainException
     */
    private function assertUserMayAddSecondFactor()
    {
        if (count($this->unverifiedSecondFactors) + count($this->verifiedSecondFactors) > 0) {
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

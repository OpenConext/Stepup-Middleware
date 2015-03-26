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
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\LoaComparable;
use Surfnet\Stepup\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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

    /**
     * @var Collection|VettedSecondFactor[]
     */
    private $vettedSecondFactors;

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

    public function bootstrapYubikeySecondFactor(SecondFactorId $secondFactorId, YubikeyPublicId $yubikeyPublicId)
    {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new YubikeySecondFactorBootstrappedEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $secondFactorId,
                $yubikeyPublicId
            )
        );
    }

    public function provePossessionOfYubikey(
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new YubikeyPossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $yubikeyPublicId,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                'en_GB'
            )
        );
    }

    public function provePossessionOfPhone(
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new PhonePossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $phoneNumber,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                'en_GB'
            )
        );
    }

    public function provePossessionOfGssf(
        SecondFactorId $secondFactorId,
        StepupProvider $provider,
        GssfId $gssfId,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertUserMayAddSecondFactor();

        $this->apply(
            new GssfPossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $provider,
                $gssfId,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                'en_GB'
            )
        );
    }

    public function verifyEmail($verificationNonce)
    {
        $secondFactorToVerify = null;
        foreach ($this->unverifiedSecondFactors as $secondFactor) {
            /** @var Entity\UnverifiedSecondFactor $secondFactor */
            if ($secondFactor->hasNonce($verificationNonce)) {
                $secondFactorToVerify = $secondFactor;
            }
        }

        if (!$secondFactorToVerify) {
            throw new DomainException(
                'Cannot verify second factor, no unverified second factor can be verified using the given nonce'
            );
        }

        /** @var Entity\UnverifiedSecondFactor $secondFactorToVerify */
        if (!$secondFactorToVerify->canBeVerifiedNow()) {
            throw new DomainException('Cannot verify second factor, the verification window is closed.');
        }

        $secondFactorToVerify->verifyEmail();
    }

    public function vetSecondFactor(
        IdentityApi $registrant,
        SecondFactorId $registrantsSecondFactorId,
        $registrantsSecondFactorIdentifier,
        $registrationCode,
        $documentNumber,
        $identityVerified
    ) {
        /** @var VettedSecondFactor|false $secondFactorWithHighestLoa */
        $secondFactorWithHighestLoa = array_reduce(
            $this->vettedSecondFactors->toArray(),
            function (VettedSecondFactor $carry, VettedSecondFactor $item) {
                return $carry->hasEqualOrHigherLoaComparedTo($item) ? $carry : $item;
            },
            $this->vettedSecondFactors->first()
        );

        $verifiedSecondFactorHasEqualOrLowerLoaComparedTo =
            $registrant->verifiedSecondFactorHasEqualOrLowerLoaComparedTo(
                $registrantsSecondFactorId,
                $secondFactorWithHighestLoa
            );

        if (!$verifiedSecondFactorHasEqualOrLowerLoaComparedTo) {
            throw new DomainException('Authority does not have the required LoA to vet the identity\'s second factor');
        }

        if (!$identityVerified) {
            throw new DomainException('Will not vet second factor when physical identity has not been verified.');
        }

        $registrant->complyWithVettingOfSecondFactor(
            $registrantsSecondFactorId,
            $registrantsSecondFactorIdentifier,
            $registrationCode,
            $documentNumber
        );
    }

    public function complyWithVettingOfSecondFactor(
        SecondFactorId $secondFactorId,
        $secondFactorIdentifier,
        $registrationCode,
        $documentNumber
    ) {
        $secondFactorToVet = null;
        foreach ($this->verifiedSecondFactors as $secondFactor) {
            /** @var VerifiedSecondFactor $secondFactor */
            if ($secondFactor->hasRegistrationCodeAndIdentifier($registrationCode, $secondFactorIdentifier)) {
                $secondFactorToVet = $secondFactor;
            }
        }

        if (!$secondFactorToVet) {
            throw new DomainException(
                'Cannot vet second factor, no verified second factor can be vetted using the given registration code ' .
                'and second factor identifier'
            );
        }

        if (!$secondFactorToVet->canBeVettedNow()) {
            throw new DomainException('Cannot vet second factor, the registration window is closed.');
        }

        $secondFactorToVet->vet($documentNumber);
    }

    public function revokeSecondFactor(SecondFactorId $secondFactorId)
    {
        /** @var UnverifiedSecondFactor|null $unverifiedSecondFactor */
        $unverifiedSecondFactor = $this->unverifiedSecondFactors->get((string) $secondFactorId);
        /** @var VerifiedSecondFactor|null $verifiedSecondFactor */
        $verifiedSecondFactor = $this->verifiedSecondFactors->get((string) $secondFactorId);
        /** @var VettedSecondFactor|null $vettedSecondFactor */
        $vettedSecondFactor = $this->vettedSecondFactors->get((string) $secondFactorId);

        if (!$unverifiedSecondFactor && !$verifiedSecondFactor && !$vettedSecondFactor) {
            throw new DomainException('Cannot revoke second factor: no second factor with given id exists.');
        }

        if ($unverifiedSecondFactor) {
            $unverifiedSecondFactor->revoke();

            return;
        }

        if ($verifiedSecondFactor) {
            $verifiedSecondFactor->revoke();

            return;
        }

        $vettedSecondFactor->revoke();
    }

    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId)
    {
        /** @var UnverifiedSecondFactor|null $unverifiedSecondFactor */
        $unverifiedSecondFactor = $this->unverifiedSecondFactors->get((string) $secondFactorId);
        /** @var VerifiedSecondFactor|null $verifiedSecondFactor */
        $verifiedSecondFactor = $this->verifiedSecondFactors->get((string) $secondFactorId);
        /** @var VettedSecondFactor|null $vettedSecondFactor */
        $vettedSecondFactor = $this->vettedSecondFactors->get((string) $secondFactorId);

        if (!$unverifiedSecondFactor && !$verifiedSecondFactor && !$vettedSecondFactor) {
            throw new DomainException('Cannot revoke second factor: no second factor with given id exists.');
        }

        if ($unverifiedSecondFactor) {
            $unverifiedSecondFactor->complyWithRevocation($authorityId);

            return;
        }

        if ($verifiedSecondFactor) {
            $verifiedSecondFactor->complyWithRevocation($authorityId);

            return;
        }

        $vettedSecondFactor->complyWithRevocation($authorityId);
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
        $this->vettedSecondFactors = new ArrayCollection();
    }

    protected function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $this->commonName = $event->newName;
    }

    protected function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $this->email = $event->newEmail;
    }

    protected function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $secondFactor = VettedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            (string) $event->yubikeyPublicId
        );

        $this->vettedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            (string) $event->yubikeyPublicId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('sms'),
            (string) $event->phoneNumber,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType((string) $event->stepupProvider),
            (string) $event->gssfId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $secondFactorId = (string) $event->secondFactorId;

        /** @var UnverifiedSecondFactor $unverified */
        $unverified = $this->unverifiedSecondFactors->get($secondFactorId);
        $verified = $unverified->asVerified($event->registrationRequestedAt, $event->registrationCode);

        $this->unverifiedSecondFactors->remove($secondFactorId);
        $this->verifiedSecondFactors->set($secondFactorId, $verified);
    }

    protected function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $secondFactorId = (string) $event->secondFactorId;

        /** @var VerifiedSecondFactor $verified */
        $verified = $this->verifiedSecondFactors->get($secondFactorId);
        $vetted = $verified->asVetted();

        $this->verifiedSecondFactors->remove($secondFactorId);
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->unverifiedSecondFactors->remove((string) $event->secondFactorId);
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->unverifiedSecondFactors->remove((string) $event->secondFactorId);
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->verifiedSecondFactors->remove((string) $event->secondFactorId);
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->verifiedSecondFactors->remove((string) $event->secondFactorId);
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->vettedSecondFactors->remove((string) $event->secondFactorId);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->vettedSecondFactors->remove((string) $event->secondFactorId);
    }

    public function getAggregateRootId()
    {
        return (string) $this->id;
    }

    protected function getChildEntities()
    {
        return array_merge(
            $this->unverifiedSecondFactors->getValues(),
            $this->verifiedSecondFactors->getValues(),
            $this->vettedSecondFactors->getValues()
        );
    }

    /**
     * @throws DomainException
     */
    private function assertUserMayAddSecondFactor()
    {
        if (count($this->unverifiedSecondFactors) +
            count($this->verifiedSecondFactors) +
            count($this->vettedSecondFactors) > 0
        ) {
            throw new DomainException('User may not have more than one token');
        }
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return NameId
     */
    public function getNameId()
    {
        return $this->nameId;
    }

    /**
     * @return Institution
     */
    public function getInstitution()
    {
        return $this->institution;
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

    public function verifiedSecondFactorHasEqualOrLowerLoaComparedTo(
        SecondFactorId $secondFactorId,
        LoaComparable $comparedTo
    ) {
        /** @var VerifiedSecondFactor|null $secondFactor */
        $secondFactor = $this->verifiedSecondFactors->get((string) $secondFactorId);

        if (!$secondFactor) {
            throw new DomainException('This identity does not have a verified second factor by that ID.');
        }

        return $comparedTo->hasEqualOrHigherLoaComparedTo($secondFactor);
    }
}

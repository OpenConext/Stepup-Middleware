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
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\RegistrationAuthority;
use Surfnet\Stepup\Identity\Entity\SecondFactorCollection;
use Surfnet\Stepup\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;
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
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    private $commonName;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    private $email;

    /**
     * @var SecondFactorCollection|UnverifiedSecondFactor[]
     */
    private $unverifiedSecondFactors;

    /**
     * @var SecondFactorCollection|VerifiedSecondFactor[]
     */
    private $verifiedSecondFactors;

    /**
     * @var SecondFactorCollection|VettedSecondFactor[]
     */
    private $vettedSecondFactors;

    /**
     * @var RegistrationAuthority
     */
    private $registrationAuthority;

    /**
     * @var Locale
     */
    private $preferredLocale;

    /**
     * @var boolean
     */
    private $forgotten;

    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $institution, $nameId, $commonName, $email, $preferredLocale));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function rename(CommonName $commonName)
    {
        $this->assertNotForgotten();

        if ($this->commonName->equals($commonName)) {
            return;
        }

        $this->commonName = $commonName;
        $this->apply(new IdentityRenamedEvent($this->id, $this->institution, $commonName));
    }

    public function changeEmail(Email $email)
    {
        $this->assertNotForgotten();

        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->apply(new IdentityEmailChangedEvent($this->id, $this->institution, $email));
    }

    public function bootstrapYubikeySecondFactor(SecondFactorId $secondFactorId, YubikeyPublicId $yubikeyPublicId)
    {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        $this->apply(
            new YubikeySecondFactorBootstrappedEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $this->commonName,
                $this->email,
                $this->preferredLocale,
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
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        $this->apply(
            new YubikeyPossessionProvenEvent(
                $this->id,
                $this->institution,
                $secondFactorId,
                $yubikeyPublicId,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                $this->preferredLocale
            )
        );
    }

    public function provePossessionOfPhone(
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        $this->apply(
            new PhonePossessionProvenEvent(
                $this->id,
                $this->institution,
                $secondFactorId,
                $phoneNumber,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                $this->preferredLocale
            )
        );
    }

    public function provePossessionOfGssf(
        SecondFactorId $secondFactorId,
        StepupProvider $provider,
        GssfId $gssfId,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        $this->apply(
            new GssfPossessionProvenEvent(
                $this->id,
                $this->institution,
                $secondFactorId,
                $provider,
                $gssfId,
                $emailVerificationWindow,
                TokenGenerator::generateNonce(),
                $this->commonName,
                $this->email,
                $this->preferredLocale
            )
        );
    }

    public function verifyEmail($verificationNonce)
    {
        $this->assertNotForgotten();

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
        SecondFactorType $registrantsSecondFactorType,
        SecondFactorIdentifier $registrantsSecondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber,
        $identityVerified
    ) {
        $this->assertNotForgotten();

        /** @var VettedSecondFactor|null $secondFactorWithHighestLoa */
        $secondFactorWithHighestLoa = $this->vettedSecondFactors->getSecondFactorWithHighestLoa();
        $registrantsSecondFactor = $registrant->getVerifiedSecondFactor($registrantsSecondFactorId);

        if ($registrantsSecondFactor === null) {
            throw new DomainException(
                sprintf('Registrant second factor with ID %s does not exist', $registrantsSecondFactorId)
            );
        }

        if (!$secondFactorWithHighestLoa->hasEqualOrHigherLoaComparedTo($registrantsSecondFactor)) {
            throw new DomainException("Authority does not have the required LoA to vet the registrant's second factor");
        }

        if (!$identityVerified) {
            throw new DomainException('Will not vet second factor when physical identity has not been verified.');
        }

        $registrant->complyWithVettingOfSecondFactor(
            $registrantsSecondFactorId,
            $registrantsSecondFactorType,
            $registrantsSecondFactorIdentifier,
            $registrationCode,
            $documentNumber
        );
    }

    public function complyWithVettingOfSecondFactor(
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber
    ) {
        $this->assertNotForgotten();

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
        $this->assertNotForgotten();

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
        $this->assertNotForgotten();

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

    /**
     * @param Institution               $institution
     * @param RegistrationAuthorityRole $role
     * @param Location                  $location
     * @param ContactInformation        $contactInformation
     * @return void
     */
    public function accreditWith(
        RegistrationAuthorityRole $role,
        Institution $institution,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $this->assertNotForgotten();

        if (!$this->institution->equals($institution)) {
            throw new DomainException('An Identity may only be accredited within its own institution');
        }

        if (!$this->vettedSecondFactors->count()) {
            throw new DomainException(
                'An Identity must have at least one vetted second factor before it can be accredited'
            );
        }

        if ($this->registrationAuthority) {
            throw new DomainException('Cannot accredit Identity as it has already been accredited');
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(new IdentityAccreditedAsRaEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $role,
                $location,
                $contactInformation
            ));
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(new IdentityAccreditedAsRaaEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $role,
                $location,
                $contactInformation
            ));
        } else {
            throw new DomainException('An Identity can only be accredited with either the RA or RAA role');
        }
    }

    public function amendRegistrationAuthorityInformation(Location $location, ContactInformation $contactInformation)
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthority) {
            throw new DomainException(
                'Cannot amend registration authority information: identity is not a registration authority'
            );
        }

        $this->apply(
            new RegistrationAuthorityInformationAmendedEvent(
                $this->id,
                $this->institution,
                $this->nameId,
                $location,
                $contactInformation
            )
        );
    }

    public function appointAs(RegistrationAuthorityRole $role)
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthority) {
            throw new DomainException(
                'Cannot appoint as different RegistrationAuthorityRole: identity is not a registration authority'
            );
        }

        if ($this->registrationAuthority->isAppointedAs($role)) {
            return;
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(new AppointedAsRaEvent($this->id, $this->institution, $this->nameId));
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(new AppointedAsRaaEvent($this->id, $this->institution, $this->nameId));
        } else {
            throw new DomainException('An Identity can only be appointed as either RA or RAA');
        }
    }

    public function retractRegistrationAuthority()
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthority) {
            throw new DomainException(
                'Cannot Retract Registration Authority as the Identity is not a registration authority'
            );
        }

        $this->apply(new RegistrationAuthorityRetractedEvent(
            $this->id,
            $this->institution,
            $this->nameId,
            $this->commonName,
            $this->email
        ));
    }

    public function expressPreferredLocale(Locale $preferredLocale)
    {
        $this->assertNotForgotten();

        if ($this->preferredLocale === $preferredLocale) {
            return;
        }

        $this->apply(new LocalePreferenceExpressedEvent($this->id, $this->institution, $preferredLocale));
    }

    public function forget()
    {
        $this->assertNotForgotten();

        if ($this->registrationAuthority) {
            throw new DomainException('Cannot forget an identity that is currently accredited as an RA(A)');
        }

        $this->apply(new IdentityForgottenEvent($this->id, $this->institution));
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id                      = $event->identityId;
        $this->institution             = $event->identityInstitution;
        $this->nameId                  = $event->nameId;
        $this->commonName              = $event->commonName;
        $this->email                   = $event->email;
        $this->preferredLocale         = $event->preferredLocale;
        $this->forgotten               = false;

        $this->unverifiedSecondFactors = new SecondFactorCollection();
        $this->verifiedSecondFactors   = new SecondFactorCollection();
        $this->vettedSecondFactors     = new SecondFactorCollection();
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $this->commonName = $event->commonName;
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $this->email = $event->email;
    }

    protected function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $secondFactor = VettedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId
        );

        $this->vettedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId,
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
            $event->phoneNumber,
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
            $event->gssfId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string) $secondFactor->getId(), $secondFactor);
    }

    protected function applyU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('u2f'),
            $event->keyHandle,
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

    protected function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $this->registrationAuthority = RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation
        );
    }

    protected function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $this->registrationAuthority = RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation
        );
    }

    protected function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event
    ) {
        $this->registrationAuthority->amendInformation($event->location, $event->contactInformation);
    }

    protected function applyAppointedAsRaEvent(AppointedAsRaEvent $event)
    {
        $this->registrationAuthority->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA));
    }

    protected function applyAppointedAsRaaEvent(AppointedAsRaaEvent $event)
    {
        $this->registrationAuthority->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA));
    }

    protected function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        $this->registrationAuthority = null;
    }

    protected function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event)
    {
        $this->preferredLocale = $event->preferredLocale;
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->commonName = CommonName::unknown();
        $this->email      = Email::unknown();
        $this->forgotten  = true;
    }

    public function getAggregateRootId()
    {
        return $this->id;
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
    private function assertNotForgotten()
    {
        if ($this->forgotten) {
            throw new DomainException('Operation on this Identity is not allowed: it has been forgotten');
        }
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

    public function getCommonName()
    {
        return $this->commonName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPreferredLocale()
    {
        return $this->preferredLocale;
    }

    /**
     * @param SecondFactorId $secondFactorId
     * @return VerifiedSecondFactor|null
     */
    public function getVerifiedSecondFactor(SecondFactorId $secondFactorId)
    {
        return $this->verifiedSecondFactors->get((string) $secondFactorId);
    }
}

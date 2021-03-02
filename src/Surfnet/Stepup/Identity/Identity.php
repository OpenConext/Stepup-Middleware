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
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Entity\RegistrationAuthority;
use Surfnet\Stepup\Identity\Entity\RegistrationAuthorityCollection;
use Surfnet\Stepup\Identity\Entity\SecondFactorCollection;
use Surfnet\Stepup\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorsAllRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
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
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;
use Surfnet\StepupBundle\Security\OtpGenerator;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
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
     * @var RegistrationAuthorityCollection
     */
    private $registrationAuthorities;

    /**
     * @var Locale
     */
    private $preferredLocale;

    /**
     * @var boolean
     */
    private $forgotten;

    /**
     * @var int
     */
    private $maxNumberOfTokens = 1;

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

    /**
     * @param int $numberOfTokens
     */
    public function setMaxNumberOfTokens($numberOfTokens)
    {
        $this->maxNumberOfTokens = $numberOfTokens;
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
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        if ($emailVerificationRequired) {
            $emailVerificationNonce = TokenGenerator::generateNonce();

            $this->apply(
                new YubikeyPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    $emailVerificationRequired,
                    $emailVerificationWindow,
                    $emailVerificationNonce,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale
                )
            );
        } else {
            $this->apply(
                new YubikeyPossessionProvenAndVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    DateTime::now(),
                    OtpGenerator::generate(8)
                )
            );
        }
    }

    public function provePossessionOfPhone(
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        if ($emailVerificationRequired) {
            $emailVerificationNonce = TokenGenerator::generateNonce();

            $this->apply(
                new PhonePossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $phoneNumber,
                    $emailVerificationRequired,
                    $emailVerificationWindow,
                    $emailVerificationNonce,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale
                )
            );
        } else {
            $this->apply(
                new PhonePossessionProvenAndVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $phoneNumber,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    DateTime::now(),
                    OtpGenerator::generate(8)
                )
            );
        }
    }

    public function provePossessionOfGssf(
        SecondFactorId $secondFactorId,
        StepupProvider $provider,
        GssfId $gssfId,
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        if ($emailVerificationRequired) {
            $emailVerificationNonce = TokenGenerator::generateNonce();

            $this->apply(
                new GssfPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $provider,
                    $gssfId,
                    $emailVerificationRequired,
                    $emailVerificationWindow,
                    $emailVerificationNonce,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale
                )
            );
        } else {
            $this->apply(
                new GssfPossessionProvenAndVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $provider,
                    $gssfId,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    DateTime::now(),
                    OtpGenerator::generate(8)
                )
            );
        }
    }

    public function provePossessionOfU2fDevice(
        SecondFactorId $secondFactorId,
        U2fKeyHandle $keyHandle,
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow
    ) {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor();

        if ($emailVerificationRequired) {
            $emailVerificationNonce = TokenGenerator::generateNonce();

            $this->apply(
                new U2fDevicePossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $keyHandle,
                    $emailVerificationRequired,
                    $emailVerificationWindow,
                    $emailVerificationNonce,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale
                )
            );
        } else {
            $this->apply(
                new U2fDevicePossessionProvenAndVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $keyHandle,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    DateTime::now(),
                    OtpGenerator::generate(8)
                )
            );
        }
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function vetSecondFactor(
        IdentityApi $registrant,
        SecondFactorId $registrantsSecondFactorId,
        SecondFactorType $registrantsSecondFactorType,
        SecondFactorIdentifier $registrantsSecondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber,
        $identityVerified,
        SecondFactorTypeService $secondFactorTypeService,
        SecondFactorProvePossessionHelper $secondFactorProvePossessionHelper,
        $provePossessionSkipped
    ) {
        $this->assertNotForgotten();

        /** @var VettedSecondFactor|null $secondFactorWithHighestLoa */
        $secondFactorWithHighestLoa = $this->vettedSecondFactors->getSecondFactorWithHighestLoa($secondFactorTypeService);
        $registrantsSecondFactor = $registrant->getVerifiedSecondFactor($registrantsSecondFactorId);

        if ($registrantsSecondFactor === null) {
            throw new DomainException(
                sprintf('Registrant second factor with ID %s does not exist', $registrantsSecondFactorId)
            );
        }

        if ($secondFactorWithHighestLoa === null) {
            throw new DomainException(
                sprintf(
                    'Vetting failed: authority %s has %d vetted second factors!',
                    $this->id,
                    count($this->vettedSecondFactors)
                )
            );
        }

        if (!$secondFactorWithHighestLoa->hasEqualOrHigherLoaComparedTo(
            $registrantsSecondFactor,
            $secondFactorTypeService
        )) {
            throw new DomainException("Authority does not have the required LoA to vet the registrant's second factor");
        }

        if (!$identityVerified) {
            throw new DomainException('Will not vet second factor when physical identity has not been verified.');
        }

        if ($provePossessionSkipped && !$secondFactorProvePossessionHelper->canSkipProvePossession($registrantsSecondFactorType)) {
            throw new DomainException(sprintf(
                "The possession of registrants second factor with ID '%s' of type '%s' has to be physically proven",
                $registrantsSecondFactorId,
                $registrantsSecondFactorType->getSecondFactorType()
            ));
        }

        $registrant->complyWithVettingOfSecondFactor(
            $registrantsSecondFactorId,
            $registrantsSecondFactorType,
            $registrantsSecondFactorIdentifier,
            $registrationCode,
            $documentNumber,
            $provePossessionSkipped
        );
    }

    public function remoteVetSecondFactor(
        SecondFactorId $secondFactorId
    ) {
        $this->assertNotForgotten();

        // TODO: Do we need configuration to whitelist remote vetting?

        /** @var UnverifiedSecondFactor|null $secondFactorWithHighestLoa */
        $secondFactor = $this->getVerifiedSecondFactor($secondFactorId);

        if ($secondFactor === null) {
            throw new DomainException(
                sprintf('Registrant second factor with ID %s does not exist', $secondFactorId)
            );
        }

        if (!$secondFactor->canBeVettedNow()) {
            throw new DomainException('Cannot vet second factor, the registration window is closed.');
        }

        $secondFactor->vet(DocumentNumber::unknown(), false);
    }

    public function complyWithVettingOfSecondFactor(
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber,
        $provePossessionSkipped
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

        $secondFactorToVet->vet($documentNumber, $provePossessionSkipped);
    }

    public function revokeSecondFactor(SecondFactorId $secondFactorId)
    {
        $this->assertNotForgotten();

        /** @var UnverifiedSecondFactor|null $unverifiedSecondFactor */
        $unverifiedSecondFactor = $this->unverifiedSecondFactors->get((string)$secondFactorId);
        /** @var VerifiedSecondFactor|null $verifiedSecondFactor */
        $verifiedSecondFactor = $this->verifiedSecondFactors->get((string)$secondFactorId);
        /** @var VettedSecondFactor|null $vettedSecondFactor */
        $vettedSecondFactor = $this->vettedSecondFactors->get((string)$secondFactorId);

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

        if ($this->vettedSecondFactors->isEmpty()) {
            $this->allVettedSecondFactorsRemoved();
        }
    }

    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId)
    {
        $this->assertNotForgotten();

        /** @var UnverifiedSecondFactor|null $unverifiedSecondFactor */
        $unverifiedSecondFactor = $this->unverifiedSecondFactors->get((string)$secondFactorId);
        /** @var VerifiedSecondFactor|null $verifiedSecondFactor */
        $verifiedSecondFactor = $this->verifiedSecondFactors->get((string)$secondFactorId);
        /** @var VettedSecondFactor|null $vettedSecondFactor */
        $vettedSecondFactor = $this->vettedSecondFactors->get((string)$secondFactorId);

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

        if ($this->vettedSecondFactors->isEmpty()) {
            $this->allVettedSecondFactorsRemoved();
        }
    }

    /**
     * @param RegistrationAuthorityRole $role
     * @param Institution $institution
     * @param Location $location
     * @param ContactInformation $contactInformation
     * @param InstitutionConfiguration $institutionConfiguration
     * @return void
     */
    public function accreditWith(
        RegistrationAuthorityRole $role,
        Institution $institution,
        Location $location,
        ContactInformation $contactInformation,
        InstitutionConfiguration $institutionConfiguration
    ) {
        $this->assertNotForgotten();

        if (!$institutionConfiguration->isInstitutionAllowedToAccreditRoles(new ConfigurationInstitution($this->institution->getInstitution()))) {
            throw new DomainException('An Identity may only be accredited by configured institutions.');
        }

        if (!$this->vettedSecondFactors->count()) {
            throw new DomainException(
                'An Identity must have at least one vetted second factor before it can be accredited'
            );
        }

        if ($this->registrationAuthorities->exists($institution)) {
            throw new DomainException('Cannot accredit Identity as it has already been accredited for institution');
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(new IdentityAccreditedAsRaForInstitutionEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $role,
                $location,
                $contactInformation,
                $institution
            ));
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(new IdentityAccreditedAsRaaForInstitutionEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $role,
                $location,
                $contactInformation,
                $institution
            ));
        } else {
            throw new DomainException('An Identity can only be accredited with either the RA or RAA role');
        }
    }

    public function amendRegistrationAuthorityInformation(Institution $institution, Location $location, ContactInformation $contactInformation)
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthorities->exists($institution)) {
            throw new DomainException(
                'Cannot amend registration authority information: identity is not a registration authority for institution'
            );
        }

        $this->apply(
            new RegistrationAuthorityInformationAmendedForInstitutionEvent(
                $this->id,
                $this->institution,
                $this->nameId,
                $location,
                $contactInformation,
                $institution
            )
        );
    }

    /**
     * This method will appoint an institution to become ra or raa for another institution
     *
     * @param Institution $institution
     * @param RegistrationAuthorityRole $role
     * @param InstitutionConfiguration $institutionConfiguration
     */
    public function appointAs(
        Institution $institution,
        RegistrationAuthorityRole $role,
        InstitutionConfiguration $institutionConfiguration
    ) {
        $this->assertNotForgotten();

        if (!$institutionConfiguration->isInstitutionAllowedToAccreditRoles(new ConfigurationInstitution($this->institution->getInstitution()))) {
            throw new DomainException(
                'Cannot appoint as different RegistrationAuthorityRole: identity is not a registration authority for institution'
            );
        }

        $registrationAuthority = $this->registrationAuthorities->get($institution);

        if ($registrationAuthority->isAppointedAs($role)) {
            return;
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(new AppointedAsRaForInstitutionEvent($this->id, $this->institution, $this->nameId, $institution));
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(new AppointedAsRaaForInstitutionEvent($this->id, $this->institution, $this->nameId, $institution));
        } else {
            throw new DomainException('An Identity can only be appointed as either RA or RAA');
        }
    }

    public function retractRegistrationAuthority(Institution $institution)
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthorities->exists($institution)) {
            throw new DomainException(
                'Cannot Retract Registration Authority as the Identity is not a registration authority'
            );
        }

        $this->apply(new RegistrationAuthorityRetractedForInstitutionEvent(
            $this->id,
            $this->institution,
            $this->nameId,
            $this->commonName,
            $this->email,
            $institution
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

        if ($this->registrationAuthorities->count()) {
            throw new DomainException('Cannot forget an identity that is currently accredited as an RA(A)');
        }

        $this->apply(new IdentityForgottenEvent($this->id, $this->institution));
    }

    public function allVettedSecondFactorsRemoved()
    {
        $this->apply(
            new VettedSecondFactorsAllRevokedEvent(
                $this->id,
                $this->institution
            )
        );
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->institution = $event->identityInstitution;
        $this->nameId = $event->nameId;
        $this->commonName = $event->commonName;
        $this->email = $event->email;
        $this->preferredLocale = $event->preferredLocale;
        $this->forgotten = false;

        $this->unverifiedSecondFactors = new SecondFactorCollection();
        $this->verifiedSecondFactors = new SecondFactorCollection();
        $this->vettedSecondFactors = new SecondFactorCollection();
        $this->registrationAuthorities = new RegistrationAuthorityCollection();
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

        $this->vettedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
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

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyYubikeyPossessionProvenAndVerifiedEvent(YubikeyPossessionProvenAndVerifiedEvent $event)
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId,
            $event->registrationRequestedAt,
            $event->registrationCode
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
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

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyPhonePossessionProvenAndVerifiedEvent(PhonePossessionProvenAndVerifiedEvent $event)
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('sms'),
            $event->phoneNumber,
            $event->registrationRequestedAt,
            $event->registrationCode
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event)
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType((string)$event->stepupProvider),
            $event->gssfId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce
        );

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyGssfPossessionProvenAndVerifiedEvent(GssfPossessionProvenAndVerifiedEvent $event)
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType((string)$event->stepupProvider),
            $event->gssfId,
            $event->registrationRequestedAt,
            $event->registrationCode
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
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

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyU2fDevicePossessionProvenAndVerifiedEvent(U2fDevicePossessionProvenAndVerifiedEvent $event)
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('u2f'),
            $event->keyHandle,
            $event->registrationRequestedAt,
            $event->registrationCode
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event)
    {
        $secondFactorId = (string)$event->secondFactorId;

        /** @var UnverifiedSecondFactor $unverified */
        $unverified = $this->unverifiedSecondFactors->get($secondFactorId);
        $verified = $unverified->asVerified($event->registrationRequestedAt, $event->registrationCode);

        $this->unverifiedSecondFactors->remove($secondFactorId);
        $this->verifiedSecondFactors->set($secondFactorId, $verified);
    }

    protected function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $secondFactorId = (string)$event->secondFactorId;

        /** @var VerifiedSecondFactor $verified */
        $verified = $this->verifiedSecondFactors->get($secondFactorId);
        $vetted = $verified->asVetted();

        $this->verifiedSecondFactors->remove($secondFactorId);
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applySecondFactorVettedWithoutTokenProofOfPossession(SecondFactorVettedWithoutTokenProofOfPossession $event)
    {
        $secondFactorId = (string)$event->secondFactorId;

        /** @var VerifiedSecondFactor $verified */
        $verified = $this->verifiedSecondFactors->get($secondFactorId);
        $vetted = $verified->asVetted();

        $this->verifiedSecondFactors->remove($secondFactorId);
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event)
    {
        $this->unverifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event
    ) {
        $this->unverifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event)
    {
        $this->verifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event
    ) {
        $this->verifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->vettedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->vettedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyIdentityAccreditedAsRaForInstitutionEvent(IdentityAccreditedAsRaForInstitutionEvent $event)
    {
        $this->registrationAuthorities->set($event->raInstitution, RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation,
            $event->raInstitution
        ));
    }

    protected function applyIdentityAccreditedAsRaaForInstitutionEvent(IdentityAccreditedAsRaaForInstitutionEvent $event)
    {
        $this->registrationAuthorities->set($event->raInstitution, RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation,
            $event->raInstitution
        ));
    }

    protected function applyRegistrationAuthorityInformationAmendedForInstitutionEvent(
        RegistrationAuthorityInformationAmendedForInstitutionEvent $event
    ) {
        $this->registrationAuthorities->get($event->raInstitution)->amendInformation($event->location, $event->contactInformation);
    }

    protected function applyAppointedAsRaaForInstitutionEvent(AppointedAsRaaForInstitutionEvent $event)
    {
        $this->registrationAuthorities->get($event->raInstitution)->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA));
    }

    protected function applyRegistrationAuthorityRetractedForInstitutionEvent(RegistrationAuthorityRetractedForInstitutionEvent $event)
    {
        $this->registrationAuthorities->remove($event->raInstitution);
    }

    protected function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event)
    {
        $this->preferredLocale = $event->preferredLocale;
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->commonName = CommonName::unknown();
        $this->email = Email::unknown();
        $this->forgotten = true;
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaEvent $event
     */
    protected function applyAppointedAsRaEvent(AppointedAsRaEvent $event)
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaaEvent $event
     */
    protected function applyAppointedAsRaaEvent(AppointedAsRaaEvent $event)
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaaEvent $event
     */
    protected function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $this->registrationAuthorities->set($event->identityInstitution, RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation,
            $event->identityInstitution
        ));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param IdentityAccreditedAsRaaEvent $event
     */
    protected function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $this->registrationAuthorities->set($event->identityInstitution, RegistrationAuthority::accreditWith(
            $event->registrationAuthorityRole,
            $event->location,
            $event->contactInformation,
            $event->identityInstitution
        ));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaForInstitutionEvent $event
     */
    protected function applyAppointedAsRaForInstitutionEvent(AppointedAsRaForInstitutionEvent $event)
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param RegistrationAuthorityInformationAmendedEvent $event
     */
    protected function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event
    ) {
        $this->registrationAuthorities->get($event->identityInstitution)->amendInformation($event->location, $event->contactInformation);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param RegistrationAuthorityRetractedEvent $event
     */
    protected function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        $this->registrationAuthorities->remove($event->identityInstitution);
    }


    public function getAggregateRootId(): string
    {
        return $this->id->getIdentityId();
    }

    protected function getChildEntities(): array
    {
        return array_merge(
            $this->unverifiedSecondFactors->getValues(),
            $this->verifiedSecondFactors->getValues(),
            $this->vettedSecondFactors->getValues(),
            $this->registrationAuthorities->getValues()
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
            count($this->vettedSecondFactors) >= $this->maxNumberOfTokens
        ) {
            throw new DomainException(
                sprintf('User may not have more than %d token(s)', $this->maxNumberOfTokens)
            );
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
        return $this->verifiedSecondFactors->get((string)$secondFactorId);
    }
}

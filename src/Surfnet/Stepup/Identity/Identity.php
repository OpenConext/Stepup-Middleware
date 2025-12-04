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
use Surfnet\Stepup\Identity\Collection\VettingTypeHintCollection;
use Surfnet\Stepup\Identity\Entity\RecoveryToken as RecoveryTokenEntity;
use Surfnet\Stepup\Identity\Entity\RecoveryTokenCollection;
use Surfnet\Stepup\Identity\Entity\RegistrationAuthority;
use Surfnet\Stepup\Identity\Entity\RegistrationAuthorityCollection;
use Surfnet\Stepup\Identity\Entity\SecondFactorCollection;
use Surfnet\Stepup\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
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
use Surfnet\Stepup\Identity\Event\IdentityRestoredEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedToEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorsAllRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettingTypeHintsSavedEvent;
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
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SafeStore;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SelfAssertedRegistrationVettingType;
use Surfnet\Stepup\Identity\Value\SelfVetVettingType;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\Stepup\Token\TokenGenerator;
use Surfnet\StepupBundle\Security\OtpGenerator;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.TooManyMethods")
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 */
class Identity extends EventSourcedAggregateRoot implements IdentityApi
{
    /**
     * @var IdentityId
     */
    private IdentityId $id;

    /**
     * @var Institution
     */
    private Institution $institution;

    /**
     * @var NameId
     */
    private NameId $nameId;

    /**
     * @var CommonName
     */
    private CommonName $commonName;

    /**
     * @var Email
     */
    private Email $email;

    private ?SecondFactorCollection $unverifiedSecondFactors = null;

    private ?SecondFactorCollection $verifiedSecondFactors = null;

    private ?SecondFactorCollection $vettedSecondFactors = null;

    private ?RegistrationAuthorityCollection $registrationAuthorities = null;

    /**
     * @var Locale
     */
    private Locale $preferredLocale;

    private ?bool $forgotten = null;

    private ?RecoveryTokenCollection $recoveryTokens = null;

    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale,
    ): self {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $institution, $nameId, $commonName, $email, $preferredLocale));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function rename(CommonName $commonName): void
    {
        $this->assertNotForgotten();

        if ($this->commonName->equals($commonName)) {
            return;
        }

        $this->commonName = $commonName;
        $this->apply(new IdentityRenamedEvent($this->id, $this->institution, $commonName));
    }

    public function changeEmail(Email $email): void
    {
        $this->assertNotForgotten();

        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->apply(new IdentityEmailChangedEvent($this->id, $this->institution, $email));
    }

    public function bootstrapYubikeySecondFactor(
        SecondFactorId  $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        int             $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $this->assertTokenTypeNotAlreadyRegistered(new SecondFactorType('yubikey'));

        $this->apply(
            new YubikeySecondFactorBootstrappedEvent(
                $this->id,
                $this->nameId,
                $this->institution,
                $this->commonName,
                $this->email,
                $this->preferredLocale,
                $secondFactorId,
                $yubikeyPublicId,
            ),
        );
    }

    public function provePossessionOfYubikey(
        SecondFactorId          $secondFactorId,
        YubikeyPublicId         $yubikeyPublicId,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $this->assertTokenTypeNotAlreadyRegistered(new SecondFactorType('yubikey'));

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
                    $this->preferredLocale,
                ),
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
                    OtpGenerator::generate(8),
                ),
            );
        }
    }

    public function provePossessionOfPhone(
        SecondFactorId          $secondFactorId,
        PhoneNumber             $phoneNumber,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $this->assertTokenTypeNotAlreadyRegistered(new SecondFactorType('sms'));

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
                    $this->preferredLocale,
                ),
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
                    OtpGenerator::generate(8),
                ),
            );
        }
    }

    public function provePossessionOfPhoneRecoveryToken(
        RecoveryTokenId $recoveryTokenId,
        PhoneNumber $phoneNumber,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddRecoveryToken(RecoveryTokenType::sms());
        $this->apply(
            new PhoneRecoveryTokenPossessionProvenEvent(
                $this->id,
                $this->institution,
                $recoveryTokenId,
                $phoneNumber,
                $this->commonName,
                $this->email,
                $this->preferredLocale,
            ),
        );
    }


    public function promisePossessionOfSafeStoreSecretRecoveryToken(RecoveryTokenId $tokenId, SafeStore $secret): void
    {
        $this->assertNotForgotten();
        $this->assertUserMayAddRecoveryToken(RecoveryTokenType::safeStore());
        $this->apply(
            new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                $this->id,
                $this->institution,
                $tokenId,
                $secret,
                $this->commonName,
                $this->email,
                $this->preferredLocale,
            ),
        );
    }

    public function saveVettingTypeHints(Institution $institution, VettingTypeHintCollection $hints): void
    {
        $this->assertNotForgotten();
        $this->apply(
            new VettingTypeHintsSavedEvent(
                $this->id,
                $this->institution,
                $hints,
                $institution,
            ),
        );
    }

    public function provePossessionOfGssf(
        SecondFactorId          $secondFactorId,
        StepupProvider          $provider,
        GssfId                  $gssfId,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $this->assertTokenTypeNotAlreadyRegistered(new SecondFactorType($provider->getStepupProvider()));

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
                    $this->preferredLocale,
                ),
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
                    OtpGenerator::generate(8),
                ),
            );
        }
    }

    /**
     * @deprecated Built in U2F support is dropped from StepUp, this was not removed to support event replay
     */
    public function provePossessionOfU2fDevice(
        SecondFactorId          $secondFactorId,
        U2fKeyHandle            $keyHandle,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $this->assertTokenTypeNotAlreadyRegistered(new SecondFactorType('u2f'));

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
                    $this->preferredLocale,
                ),
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
                    OtpGenerator::generate(8),
                ),
            );
        }
    }

    public function verifyEmail(string $verificationNonce): void
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
                'Cannot verify second factor, no unverified second factor can be verified using the given nonce',
            );
        }

        /** @var Entity\UnverifiedSecondFactor $secondFactorToVerify */
        if (!$secondFactorToVerify->canBeVerifiedNow()) {
            throw new DomainException('Cannot verify second factor, the verification window is closed.');
        }

        $secondFactorToVerify->verifyEmail();
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function vetSecondFactor(
        IdentityApi                       $registrant,
        SecondFactorId                    $registrantsSecondFactorId,
        SecondFactorType                  $registrantsSecondFactorType,
        SecondFactorIdentifier            $registrantsSecondFactorIdentifier,
        string                            $registrationCode,
        DocumentNumber                    $documentNumber,
        bool                              $identityVerified,
        SecondFactorTypeService           $secondFactorTypeService,
        SecondFactorProvePossessionHelper $secondFactorProvePossessionHelper,
        bool                              $provePossessionSkipped,
    ): void {
        $this->assertNotForgotten();

        /** The vetted second factor collection can determine highest loa based on the vetting type,
         * the other can not (as the verified and unverified second factors do not have a vetting type)
         * And the vetting type is used to determine if the LoA is diminished (in case of a self
         * asserted token registration)
         */
        /** @var VettedSecondFactor|null $secondFactorWithHighestLoa */
        $secondFactorWithHighestLoa = $this->vettedSecondFactors->getSecondFactorWithHighestLoa(
            $secondFactorTypeService,
        );
        $registrantsSecondFactor = $registrant->getVerifiedSecondFactor($registrantsSecondFactorId);

        if (!$registrantsSecondFactor instanceof VerifiedSecondFactor) {
            throw new DomainException(
                sprintf('Registrant second factor with ID %s does not exist', $registrantsSecondFactorId),
            );
        }

        if ($secondFactorWithHighestLoa === null) {
            throw new DomainException(
                sprintf(
                    'Vetting failed: authority %s has %d vetted second factors!',
                    $this->id,
                    count($this->vettedSecondFactors),
                ),
            );
        }

        if (!$secondFactorWithHighestLoa->hasEqualOrHigherLoaComparedTo(
            $registrantsSecondFactor,
            $secondFactorTypeService,
        )) {
            throw new DomainException("Authority does not have the required LoA to vet the registrant's second factor");
        }

        if (!$identityVerified) {
            throw new DomainException('Will not vet second factor when physical identity has not been verified.');
        }

        if ($provePossessionSkipped && !$secondFactorProvePossessionHelper->canSkipProvePossession(
            $registrantsSecondFactorType,
        )) {
            throw new DomainException(
                sprintf(
                    "The possession of registrants second factor with ID '%s' of type '%s' has to be physically proven",
                    $registrantsSecondFactorId,
                    $registrantsSecondFactorType->getSecondFactorType(),
                ),
            );
        }

        $registrant->complyWithVettingOfSecondFactor(
            $registrantsSecondFactorId,
            $registrantsSecondFactorType,
            $registrantsSecondFactorIdentifier,
            $registrationCode,
            $documentNumber,
            $provePossessionSkipped,
        );
    }

    public function registerSelfAssertedSecondFactor(
        SecondFactorIdentifier $secondFactorIdentifier,
        SecondFactorTypeService $secondFactorTypeService,
        RecoveryTokenId $recoveryTokenId,
    ): void {
        $this->assertNotForgotten();
        $this->assertSelfAssertedTokenRegistrationAllowed();

        try {
            $recoveryToken = $this->recoveryTokens->get($recoveryTokenId);
        } catch (DomainException) {
            throw new DomainException(
                sprintf('Recovery token used during registration is not possessed by identity %s', (string)$this->id),
            );
        }

        $registeringSecondFactor = null;
        foreach ($this->verifiedSecondFactors as $secondFactor) {
            if ($secondFactorIdentifier->equals($secondFactor->getIdentifier())) {
                $registeringSecondFactor = $secondFactor;
            }
        }

        if ($registeringSecondFactor === null) {
            throw new DomainException(
                sprintf(
                    'Registering second factor of type %s with ID %s does not exist',
                    $secondFactorIdentifier::class,
                    $secondFactorIdentifier->getValue(),
                ),
            );
        }
        $registeringSecondFactor->vet(true, new SelfAssertedRegistrationVettingType($recoveryToken->getTokenId()));
    }

    /**
     * Two self-vet scenarios are dealt with
     *
     * 1. A regular self-vet action. Where an on premise token is used to vet another token
     *    from the comfort of the identity's SelfService application. In other words, self vetting
     *    allows the identity to activate a second/third/.. token without visiting the service desk
     *
     * 2. A variation on 1: but here a self-asserted token is used to activate the verified token.
     *    This new token will inherit the LoA of the self-asserted token. Effectively giving it a
     *    LoA 1.5 level.
     *
     * The code below uses the following terminology
     *
     *   RegisteringSecondFactor: This is the verified second factor that is to be activated
     *                            using the self-vet vetting type
     *   AuthoringSecondFactor:   The vetted token, used to activate (vet) the RegisteringSecondFactor
     *   IsSelfVetUsingSAT:       Is self-vetting using a self-asserted token allowed for this
     *                            self-vet scenario? All existing vetted tokens must be of the
     *                            self-asserted vetting type.
     *
     */
    public function selfVetSecondFactor(
        Loa $authoringSecondFactorLoa,
        string $registrationCode,
        SecondFactorIdentifier $secondFactorIdentifier,
        SecondFactorTypeService $secondFactorTypeService,
    ): void {
        $this->assertNotForgotten();
        $registeringSecondFactor = null;
        foreach ($this->verifiedSecondFactors as $secondFactor) {
            /** @var VerifiedSecondFactor $secondFactor */
            if ($secondFactor->hasRegistrationCodeAndIdentifier($registrationCode, $secondFactorIdentifier)) {
                $registeringSecondFactor = $secondFactor;
            }
        }

        if ($registeringSecondFactor === null) {
            throw new DomainException(
                sprintf(
                    'Registrant second factor of type %s with ID %s does not exist',
                    $secondFactorIdentifier::class,
                    $secondFactorIdentifier->getValue(),
                ),
            );
        }

        if (!$registeringSecondFactor->hasRegistrationCodeAndIdentifier($registrationCode, $secondFactorIdentifier)) {
            throw new DomainException('The verified second factors registration code or identifier do not match.');
        }

        $selfVettingIsAllowed = $authoringSecondFactorLoa->levelIsHigherOrEqualTo(
            $registeringSecondFactor->getLoaLevel($secondFactorTypeService),
        );

        // Was the authorizing token a self-asserted token (does it have LoA 1.5?)
        $isSelfVetUsingSAT = $authoringSecondFactorLoa->getLevel() === Loa::LOA_SELF_VETTED;

        if (!$selfVettingIsAllowed && !$isSelfVetUsingSAT) {
            throw new DomainException(
                "The second factor to be vetted has a higher LoA then the Token used for proving possession",
            );
        }

        if ($isSelfVetUsingSAT) {
            // Assert that all previously vetted tokens are SAT tokens. If this is not the case, do not allow
            // self vetting using a SAT.
            $this->assertAllVettedTokensAreSelfAsserted();
            $recoveryToken = $this->recoveryTokens->first();
            $registeringSecondFactor->vet(true, new SelfAssertedRegistrationVettingType($recoveryToken->getTokenId()));
            return;
        }
        $registeringSecondFactor->vet(true, new SelfVetVettingType($authoringSecondFactorLoa));
    }

    /**
     * Copy a token from the source identity to the target identity
     */
    public function migrateVettedSecondFactor(
        IdentityApi $sourceIdentity,
        SecondFactorId $secondFactorId,
        string $targetSecondFactorId,
        int $maxNumberOfTokens,
    ): void {
        $this->assertNotForgotten();
        $this->assertUserMayAddSecondFactor($maxNumberOfTokens);
        $secondFactor = $sourceIdentity->getVettedSecondFactorById($secondFactorId);
        if (!$secondFactor instanceof VettedSecondFactor) {
            throw new DomainException("The second factor on the original identity can not be found");
        }
        $this->assertTokenNotAlreadyRegistered($secondFactor->getType(), $secondFactor->getIdentifier());
        $this->assertTokenTypeNotAlreadyRegistered($secondFactor->getType());
        if ($sourceIdentity->getInstitution()->equals($this->getInstitution())) {
            throw new DomainException("Cannot move the second factor to the same institution");
        }

        $this->apply(
            new SecondFactorMigratedEvent(
                $this->getId(),
                $this->getNameId(),
                $this->getInstitution(),
                $sourceIdentity->getInstitution(),
                $secondFactorId,
                new SecondFactorId($targetSecondFactorId),
                $secondFactor->getType(),
                $secondFactor->getIdentifier(),
                $secondFactor->vettingType(),
                $this->getCommonName(),
                $this->getEmail(),
                $this->getPreferredLocale(),
            ),
        );

        $this->apply(
            new SecondFactorMigratedToEvent(
                $sourceIdentity->getId(),
                $sourceIdentity->getInstitution(),
                $this->getInstitution(),
                $secondFactor->getId(),
                new SecondFactorId($targetSecondFactorId),
                $secondFactor->getType(),
                $secondFactor->getIdentifier(),
            ),
        );
    }

    public function complyWithVettingOfSecondFactor(
        SecondFactorId         $secondFactorId,
        SecondFactorType       $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        string                 $registrationCode,
        DocumentNumber         $documentNumber,
        bool                   $provePossessionSkipped,
    ): void {
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
                'and second factor identifier',
            );
        }

        if (!$secondFactorToVet->canBeVettedNow()) {
            throw new DomainException('Cannot vet second factor, the registration window is closed.');
        }

        $secondFactorToVet->vet($provePossessionSkipped, new OnPremiseVettingType($documentNumber));
    }

    public function revokeSecondFactor(SecondFactorId $secondFactorId): void
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

    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId): void
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

    public function revokeRecoveryToken(RecoveryTokenId $recoveryTokenId): void
    {
        $this->assertNotForgotten();
        try {
            $recoveryToken = $this->recoveryTokens->get($recoveryTokenId);
        } catch (DomainException $e) {
            throw new DomainException('Cannot revoke recovery token: no token with given id exists.', 0, $e);
        }
        $recoveryToken->revoke();
    }

    public function complyWithRecoveryTokenRevocation(RecoveryTokenId $recoveryTokenId, IdentityId $authorityId): void
    {
        $this->assertNotForgotten();
        try {
            $recoveryToken = $this->recoveryTokens->get($recoveryTokenId);
        } catch (DomainException $e) {
            throw new DomainException('Cannot revoke recovery token: no token with given id exists.', 0, $e);
        }
        $recoveryToken->complyWithRevocation($authorityId);
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
        InstitutionConfiguration $institutionConfiguration,
    ): void {
        $this->assertNotForgotten();

        if (!$institutionConfiguration->isInstitutionAllowedToAccreditRoles(
            new ConfigurationInstitution($this->institution->getInstitution()),
        )) {
            throw new DomainException('An Identity may only be accredited by configured institutions.');
        }

        if (!$this->vettedSecondFactors->count()) {
            throw new DomainException(
                'An Identity must have at least one vetted second factor before it can be accredited',
            );
        }

        if ($this->registrationAuthorities->exists($institution)) {
            throw new DomainException('Cannot accredit Identity as it has already been accredited for institution');
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $role,
                    $location,
                    $contactInformation,
                    $institution,
                ),
            );
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(
                new IdentityAccreditedAsRaaForInstitutionEvent(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $role,
                    $location,
                    $contactInformation,
                    $institution,
                ),
            );
        } else {
            throw new DomainException('An Identity can only be accredited with either the RA or RAA role');
        }
    }

    public function amendRegistrationAuthorityInformation(
        Institution $institution,
        Location $location,
        ContactInformation $contactInformation,
    ): void {
        $this->assertNotForgotten();

        if (!$this->registrationAuthorities->exists($institution)) {
            throw new DomainException(
                'Cannot amend registration authority information: identity is not a registration authority for institution',
            );
        }

        $this->apply(
            new RegistrationAuthorityInformationAmendedForInstitutionEvent(
                $this->id,
                $this->institution,
                $this->nameId,
                $location,
                $contactInformation,
                $institution,
            ),
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
        InstitutionConfiguration $institutionConfiguration,
    ): void {
        $this->assertNotForgotten();

        if (!$institutionConfiguration->isInstitutionAllowedToAccreditRoles(
            new ConfigurationInstitution($this->institution->getInstitution()),
        )) {
            throw new DomainException(
                'Cannot appoint as different RegistrationAuthorityRole: identity is not a registration authority for institution',
            );
        }

        $registrationAuthority = $this->registrationAuthorities->get($institution);

        if ($registrationAuthority->isAppointedAs($role)) {
            return;
        }

        if ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA))) {
            $this->apply(
                new AppointedAsRaForInstitutionEvent($this->id, $this->institution, $this->nameId, $institution),
            );
        } elseif ($role->equals(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA))) {
            $this->apply(
                new AppointedAsRaaForInstitutionEvent($this->id, $this->institution, $this->nameId, $institution),
            );
        } else {
            throw new DomainException('An Identity can only be appointed as either RA or RAA');
        }
    }

    public function retractRegistrationAuthority(Institution $institution): void
    {
        $this->assertNotForgotten();

        if (!$this->registrationAuthorities->exists($institution)) {
            throw new DomainException(
                'Cannot Retract Registration Authority as the Identity is not a registration authority',
            );
        }

        $this->apply(
            new RegistrationAuthorityRetractedForInstitutionEvent(
                $this->id,
                $this->institution,
                $this->nameId,
                $this->commonName,
                $this->email,
                $institution,
            ),
        );
    }

    public function expressPreferredLocale(Locale $preferredLocale): void
    {
        $this->assertNotForgotten();

        if ($this->preferredLocale === $preferredLocale) {
            return;
        }

        $this->apply(new LocalePreferenceExpressedEvent($this->id, $this->institution, $preferredLocale));
    }

    public function forget(): void
    {
        if ($this->registrationAuthorities->count() !== 0) {
            throw new DomainException('Cannot forget an identity that is currently accredited as an RA(A)');
        }

        $this->apply(new IdentityForgottenEvent($this->id, $this->institution));
    }

    public function restore(
        CommonName $commonName,
        Email $email,
    ): void {
        if (!$this->forgotten) {
            return;
        }

        $this->apply(new IdentityRestoredEvent($this->id, $this->institution, $commonName, $email));
    }

    public function allVettedSecondFactorsRemoved(): void
    {
        $this->apply(
            new VettedSecondFactorsAllRevokedEvent(
                $this->id,
                $this->institution,
            ),
        );
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event): void
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
        $this->recoveryTokens = new RecoveryTokenCollection();
    }

    protected function applyIdentityRestoredEvent(IdentityRestoredEvent $event): void
    {
        $this->unverifiedSecondFactors = new SecondFactorCollection();
        $this->verifiedSecondFactors = new SecondFactorCollection();
        $this->vettedSecondFactors = new SecondFactorCollection();
        $this->registrationAuthorities = new RegistrationAuthorityCollection();
        $this->recoveryTokens = new RecoveryTokenCollection();

        $this->commonName = $event->commonName;
        $this->email = $event->email;
        $this->forgotten = false;
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event): void
    {
        $this->commonName = $event->commonName;
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event): void
    {
        $this->email = $event->email;
    }

    protected function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event): void
    {
        $secondFactor = VettedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId,
            new UnknownVettingType(),
        );

        $this->vettedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event): void
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce,
        );

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyYubikeyPossessionProvenAndVerifiedEvent(YubikeyPossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('yubikey'),
            $event->yubikeyPublicId,
            $event->registrationRequestedAt,
            $event->registrationCode,
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event): void
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('sms'),
            $event->phoneNumber,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce,
        );

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyPhonePossessionProvenAndVerifiedEvent(PhonePossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('sms'),
            $event->phoneNumber,
            $event->registrationRequestedAt,
            $event->registrationCode,
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyGssfPossessionProvenEvent(GssfPossessionProvenEvent $event): void
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType((string)$event->stepupProvider),
            $event->gssfId,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce,
        );

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyGssfPossessionProvenAndVerifiedEvent(GssfPossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType((string)$event->stepupProvider),
            $event->gssfId,
            $event->registrationRequestedAt,
            $event->registrationCode,
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyU2fDevicePossessionProvenEvent(U2fDevicePossessionProvenEvent $event): void
    {
        $secondFactor = UnverifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('u2f'),
            $event->keyHandle,
            $event->emailVerificationWindow,
            $event->emailVerificationNonce,
        );

        $this->unverifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyU2fDevicePossessionProvenAndVerifiedEvent(U2fDevicePossessionProvenAndVerifiedEvent $event): void
    {
        $secondFactor = VerifiedSecondFactor::create(
            $event->secondFactorId,
            $this,
            new SecondFactorType('u2f'),
            $event->keyHandle,
            $event->registrationRequestedAt,
            $event->registrationCode,
        );

        $this->verifiedSecondFactors->set((string)$secondFactor->getId(), $secondFactor);
    }

    protected function applyPhoneRecoveryTokenPossessionProvenEvent(PhoneRecoveryTokenPossessionProvenEvent $event): void
    {
        $recoveryToken = RecoveryTokenEntity::create($event->recoveryTokenId, RecoveryTokenType::sms(), $this);

        $this->recoveryTokens->set($recoveryToken);
    }

    protected function applySafeStoreSecretRecoveryTokenPossessionPromisedEvent(
        SafeStoreSecretRecoveryTokenPossessionPromisedEvent $event,
    ): void {
        $recoveryToken = RecoveryTokenEntity::create($event->recoveryTokenId, RecoveryTokenType::safeStore(), $this);

        $this->recoveryTokens->set($recoveryToken);
    }

    protected function applyEmailVerifiedEvent(EmailVerifiedEvent $event): void
    {
        $secondFactorId = (string)$event->secondFactorId;

        /** @var UnverifiedSecondFactor $unverified */
        $unverified = $this->unverifiedSecondFactors->get($secondFactorId);
        $verified = $unverified->asVerified($event->registrationRequestedAt, $event->registrationCode);

        $this->unverifiedSecondFactors->remove($secondFactorId);
        $this->verifiedSecondFactors->set($secondFactorId, $verified);
    }

    /**
     * The SecondFactorMigratedToEvent is applied by creating a new
     * vetted second factor on the target identity. The source
     * second factor is not yet forgotten.
     */
    public function applySecondFactorMigratedEvent(SecondFactorMigratedEvent $event): void
    {
        $secondFactorId = (string)$event->newSecondFactorId;
        $vetted = VettedSecondFactor::create(
            $event->newSecondFactorId,
            $this,
            $event->secondFactorType,
            $event->secondFactorIdentifier,
            $event->vettingType,
        );
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applySecondFactorVettedEvent(SecondFactorVettedEvent $event): void
    {
        $secondFactorId = (string)$event->secondFactorId;
        $verified = $this->verifiedSecondFactors->get($secondFactorId);
        $vetted = $verified->asVetted($event->vettingType);
        $this->verifiedSecondFactors->remove($secondFactorId);
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applySecondFactorVettedWithoutTokenProofOfPossession(
        SecondFactorVettedWithoutTokenProofOfPossession $event,
    ): void {
        $secondFactorId = (string)$event->secondFactorId;

        /** @var VerifiedSecondFactor $verified */
        $verified = $this->verifiedSecondFactors->get($secondFactorId);
        $vetted = $verified->asVetted($event->vettingType);

        $this->verifiedSecondFactors->remove($secondFactorId);
        $this->vettedSecondFactors->set($secondFactorId, $vetted);
    }

    protected function applyUnverifiedSecondFactorRevokedEvent(UnverifiedSecondFactorRevokedEvent $event): void
    {
        $this->unverifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithUnverifiedSecondFactorRevocationEvent(
        CompliedWithUnverifiedSecondFactorRevocationEvent $event,
    ): void {
        $this->unverifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyVerifiedSecondFactorRevokedEvent(VerifiedSecondFactorRevokedEvent $event): void
    {
        $this->verifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithVerifiedSecondFactorRevocationEvent(
        CompliedWithVerifiedSecondFactorRevocationEvent $event,
    ): void {
        $this->verifiedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event): void
    {
        $this->vettedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event,
    ): void {
        $this->vettedSecondFactors->remove((string)$event->secondFactorId);
    }

    protected function applyCompliedWithRecoveryCodeRevocationEvent(CompliedWithRecoveryCodeRevocationEvent $event): void
    {
        $this->recoveryTokens->remove($event->recoveryTokenId);
    }

    protected function applyRecoveryTokenRevokedEvent(RecoveryTokenRevokedEvent $event): void
    {
        $this->recoveryTokens->remove($event->recoveryTokenId);
    }

    protected function applyIdentityAccreditedAsRaForInstitutionEvent(IdentityAccreditedAsRaForInstitutionEvent $event): void
    {
        $this->registrationAuthorities->set(
            $event->raInstitution,
            RegistrationAuthority::accreditWith(
                $event->registrationAuthorityRole,
                $event->location,
                $event->contactInformation,
                $event->raInstitution,
            ),
        );
    }

    protected function applyIdentityAccreditedAsRaaForInstitutionEvent(IdentityAccreditedAsRaaForInstitutionEvent $event,): void
    {
        $this->registrationAuthorities->set(
            $event->raInstitution,
            RegistrationAuthority::accreditWith(
                $event->registrationAuthorityRole,
                $event->location,
                $event->contactInformation,
                $event->raInstitution,
            ),
        );
    }

    protected function applyRegistrationAuthorityInformationAmendedForInstitutionEvent(
        RegistrationAuthorityInformationAmendedForInstitutionEvent $event,
    ): void {
        $this->registrationAuthorities->get($event->raInstitution)->amendInformation(
            $event->location,
            $event->contactInformation,
        );
    }

    protected function applyAppointedAsRaaForInstitutionEvent(AppointedAsRaaForInstitutionEvent $event): void
    {
        $this->registrationAuthorities->get($event->raInstitution)->appointAs(
            new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
        );
    }

    protected function applyRegistrationAuthorityRetractedForInstitutionEvent(
        RegistrationAuthorityRetractedForInstitutionEvent $event,
    ): void {
        $this->registrationAuthorities->remove($event->raInstitution);
    }

    protected function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event): void
    {
        $this->preferredLocale = $event->preferredLocale;
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $this->commonName = CommonName::unknown();
        $this->email = Email::unknown();
        $this->forgotten = true;
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyAppointedAsRaEvent(AppointedAsRaEvent $event): void
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyAppointedAsRaaEvent(AppointedAsRaaEvent $event): void
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event): void
    {
        $this->registrationAuthorities->set(
            $event->identityInstitution,
            RegistrationAuthority::accreditWith(
                $event->registrationAuthorityRole,
                $event->location,
                $event->contactInformation,
                $event->identityInstitution,
            ),
        );
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event): void
    {
        $this->registrationAuthorities->set(
            $event->identityInstitution,
            RegistrationAuthority::accreditWith(
                $event->registrationAuthorityRole,
                $event->location,
                $event->contactInformation,
                $event->identityInstitution,
            ),
        );
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyAppointedAsRaForInstitutionEvent(AppointedAsRaForInstitutionEvent $event): void
    {
        $this->registrationAuthorities->get($event->identityInstitution)
            ->appointAs(new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event,
    ): void {
        $this->registrationAuthorities->get($event->identityInstitution)->amendInformation(
            $event->location,
            $event->contactInformation,
        );
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     */
    protected function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event): void
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
            $this->registrationAuthorities->getValues(),
        );
    }

    /**
     * @throws DomainException
     */
    private function assertNotForgotten(): void
    {
        if ($this->forgotten) {
            throw new DomainException('Operation on this Identity is not allowed: it has been forgotten');
        }
    }

    /**
     * @throws DomainException
     */
    private function assertUserMayAddSecondFactor(int $maxNumberOfTokens): void
    {
        $tokenCount = (int) count($this->unverifiedSecondFactors) + count($this->verifiedSecondFactors) + count($this->vettedSecondFactors);
        if ($tokenCount >= $maxNumberOfTokens
        ) {
            throw new DomainException(
                sprintf('User may not have more than %d token(s)', $maxNumberOfTokens),
            );
        }
    }

    private function assertUserMayAddRecoveryToken(RecoveryTokenType $recoveryTokenType): void
    {
        // Assert this token type is not yet registered
        if ($this->recoveryTokens->hasType($recoveryTokenType)) {
            throw new DomainException(
                sprintf('Recovery token type %s is already registered', (string)$recoveryTokenType),
            );
        }
    }

    public function getId(): IdentityId
    {
        return $this->id;
    }

    /**
     * @return NameId
     */
    public function getNameId(): NameId
    {
        return $this->nameId;
    }

    /**
     * @return Institution
     */
    public function getInstitution(): Institution
    {
        return $this->institution;
    }

    public function getCommonName(): CommonName
    {
        return $this->commonName;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPreferredLocale(): Locale
    {
        return $this->preferredLocale;
    }

    public function getVerifiedSecondFactor(SecondFactorId $secondFactorId): ?VerifiedSecondFactor
    {
        return $this->verifiedSecondFactors->get((string)$secondFactorId);
    }

    public function getVettedSecondFactorById(SecondFactorId $secondFactorId): ?VettedSecondFactor
    {
        return $this->vettedSecondFactors->get((string)$secondFactorId);
    }

    private function assertTokenNotAlreadyRegistered(SecondFactorType $type, SecondFactorIdentifier $identifier): void
    {
        foreach ($this->unverifiedSecondFactors as $unverified) {
            if ($unverified->typeAndIdentifierAreEqual($type, $identifier)) {
                throw new DomainException("The second factor was already registered as an unverified second factor");
            }
        }
        foreach ($this->verifiedSecondFactors as $verified) {
            if ($verified->typeAndIdentifierAreEqual($type, $identifier)) {
                throw new DomainException("The second factor was already registered as a verified second factor");
            }
        }
        foreach ($this->vettedSecondFactors as $vetted) {
            if ($vetted->typeAndIdentifierAreEqual($type, $identifier)) {
                throw new DomainException("The second factor was registered as a vetted second factor");
            }
        }
    }

    private function assertTokenTypeNotAlreadyRegistered(SecondFactorType $type): void
    {
        foreach ($this->unverifiedSecondFactors as $unverified) {
            if ($unverified->getType()->equals($type)) {
                throw new DomainException("This second factor type was already registered as an unverified second factor");
            }
        }
        foreach ($this->verifiedSecondFactors as $verified) {
            if ($verified->getType()->equals($type)) {
                throw new DomainException("This second factor type was already registered as a verified second factor");
            }
        }
        foreach ($this->vettedSecondFactors as $vetted) {
            if ($vetted->getType()->equals($type)) {
                throw new DomainException("This second factor type was already registered as a vetted second factor");
            }
        }
    }

    private function assertSelfAssertedTokenRegistrationAllowed(): void
    {
        if ($this->vettedSecondFactors->count() !== 0) {
            throw new DomainException(
                "Self-asserted second factor registration is only allowed when no tokens are vetted yet",
            );
        }
        if ($this->recoveryTokens->count() === 0) {
            throw new DomainException("A recovery token is required to perform a self-asserted token registration");
        }
    }

    /**
     * Verify that every vetted second factor is self-asserted
     */
    private function assertAllVettedTokensAreSelfAsserted(): void
    {
        /** @var VettedSecondFactor $vettedSecondFactor */
        foreach ($this->vettedSecondFactors as $vettedSecondFactor) {
            if ($vettedSecondFactor->vettingType()->type() !== VettingType::TYPE_SELF_ASSERTED_REGISTRATION) {
                throw new DomainException(
                    'Not all tokens are self-asserted, it is not allowed to self-vet using the self-asserted token',
                );
            }
        }
    }
}

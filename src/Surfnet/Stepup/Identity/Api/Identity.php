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

namespace Surfnet\Stepup\Identity\Api;

use Broadway\Domain\AggregateRoot;
use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Identity\Collection\VettingTypeHintCollection;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
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
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SafeStore;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;

interface Identity extends AggregateRoot
{
    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale,
    ): Identity;

    /**
     * Construct a new aggregate root. Aggregate roots can only be affected by events, so no parameters are allowed.
     */
    public function __construct();

    public function rename(CommonName $commonName): void;

    public function changeEmail(Email $email): void;

    public function bootstrapYubikeySecondFactor(
        SecondFactorId  $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        int             $maxNumberOfTokens,
    ): void;

    public function provePossessionOfYubikey(
        SecondFactorId          $secondFactorId,
        YubikeyPublicId         $yubikeyPublicId,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void;

    public function provePossessionOfPhone(
        SecondFactorId          $secondFactorId,
        PhoneNumber             $phoneNumber,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void;

    /**
     * @parame int $maxNumberOfTokens
     */
    public function provePossessionOfGssf(
        SecondFactorId          $secondFactorId,
        StepupProvider          $provider,
        GssfId                  $gssfId,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void;

    /**
     * @parame int $maxNumberOfTokens
     * @deprecated Built in U2F support is dropped from StepUp, this was not removed to support event replay
     */
    public function provePossessionOfU2fDevice(
        SecondFactorId          $secondFactorId,
        U2fKeyHandle            $keyHandle,
        bool                    $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        int                     $maxNumberOfTokens,
    ): void;

    /**
     * @return void
     */
    public function verifyEmail(string $verificationNonce): void;

    /**
     * Attempts to vet another identity's verified second factor.
     *
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function vetSecondFactor(
        Identity                          $registrant,
        SecondFactorId                    $registrantsSecondFactorId,
        SecondFactorType                  $registrantsSecondFactorType,
        SecondFactorIdentifier            $registrantsSecondFactorIdentifier,
        string                            $registrationCode,
        DocumentNumber                    $documentNumber,
        bool                              $identityVerified,
        SecondFactorTypeService           $secondFactorTypeService,
        SecondFactorProvePossessionHelper $secondFactorProvePossessionHelper,
        bool                              $provePossessionSkipped,
    ): void;

    /**
     * Self vetting, is when the user uses its own token to vet another.
     *
     * Here the new token should have a lower or equal LoA to that of the one in possession of the identity
     *
     * Alternatively, the selfVetSecondFactor allows for vetting of self-asserted tokens. In that case, a
     * token that was activated using a self-asserted vetting method, is used to author the possession of
     * a new verified token. Note that this newly self-vetted token will have a diminished LoA. This because
     * the self-asserted token used to author itself has a deminished LoA level due to the lack of a vetted
     * identity.
     */
    public function selfVetSecondFactor(
        Loa $authoringSecondFactorLoa,
        string $registrationCode,
        SecondFactorIdentifier $secondFactorIdentifier,
        SecondFactorTypeService $secondFactorTypeService,
    ): void;

    public function registerSelfAssertedSecondFactor(
        SecondFactorIdentifier $secondFactorIdentifier,
        SecondFactorTypeService $secondFactorTypeService,
        RecoveryTokenId $recoveryTokenId,
    ): void;

    /**
     * Migrate a token from the source identity to the target identity
     */
    public function migrateVettedSecondFactor(
        Identity $sourceIdentity,
        SecondFactorId $secondFactorId,
        string $targetSecondFactorId,
        int $maxNumberOfTokens,
    ): void;

    /**
     * Makes the identity comply with an authority's vetting of a verified second factor.
     *
     * @throws DomainException
     */
    public function complyWithVettingOfSecondFactor(
        SecondFactorId         $secondFactorId,
        SecondFactorType       $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        string                 $registrationCode,
        DocumentNumber         $documentNumber,
        bool                   $provePossessionSkipped,
    ): void;

    public function revokeSecondFactor(SecondFactorId $secondFactorId): void;

    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId): void;

    /**
     * From SelfService, an Identity is allowed to revoke a recovery token
     */
    public function revokeRecoveryToken(RecoveryTokenId $recoveryTokenId): void;

    /**
     * RA(A) users are allowed on behalf of an Identity to revoke a
     * recovery token.
     */
    public function complyWithRecoveryTokenRevocation(RecoveryTokenId $recoveryTokenId, IdentityId $authorityId): void;

    public function accreditWith(
        RegistrationAuthorityRole $role,
        Institution $institution,
        Location $location,
        ContactInformation $contactInformation,
        InstitutionConfiguration $institutionConfiguration,
    ): void;

    public function appointAs(
        Institution $institution,
        RegistrationAuthorityRole $role,
        InstitutionConfiguration $institutionConfiguration,
    ): void;

    public function amendRegistrationAuthorityInformation(
        Institution $institution,
        Location $location,
        ContactInformation $contactInformation,
    ): void;

    public function retractRegistrationAuthority(Institution $institution): void;

    public function expressPreferredLocale(Locale $preferredLocale): void;

    /**
     * @return void
     */
    public function forget(): void;

    /**
     * @return IdentityId
     */
    public function getId(): IdentityId;

    /**
     * @return NameId
     */
    public function getNameId(): NameId;

    /**
     * @return Institution
     */
    public function getInstitution(): Institution;

    /**
     * @return CommonName
     */
    public function getCommonName(): CommonName;

    /**
     * @return Email
     */
    public function getEmail(): Email;

    /**
     * @return Locale
     */
    public function getPreferredLocale(): Locale;

    public function getVerifiedSecondFactor(SecondFactorId $secondFactorId): ?VerifiedSecondFactor;

    public function getVettedSecondFactorById(SecondFactorId $secondFactorId): ?VettedSecondFactor;

    /**
     * Identity proved possession of a phone number by reproducing a secret sent to it via SMS
     */
    public function provePossessionOfPhoneRecoveryToken(
        RecoveryTokenId $recoveryTokenId,
        PhoneNumber $phoneNumber,
    ): void;

    /**
     * Identity promises it stored the once printed on screen password in a safe location
     */
    public function promisePossessionOfSafeStoreSecretRecoveryToken(RecoveryTokenId $tokenId, SafeStore $secret): void;

    public function saveVettingTypeHints(Institution $institution, VettingTypeHintCollection $hints): void;
}

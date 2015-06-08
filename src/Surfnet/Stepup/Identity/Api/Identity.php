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
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
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
use Surfnet\StepupBundle\Value\SecondFactorType;

interface Identity extends AggregateRoot
{
    /**
     * @param IdentityId  $id
     * @param Institution $institution
     * @param NameId      $nameId
     * @param CommonName  $commonName
     * @param Email       $email
     * @param Locale      $preferredLocale
     * @return Identity
     */
    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    );

    /**
     * Construct a new aggregate root. Aggregate roots can only be affected by events, so no parameters are allowed.
     */
    public function __construct();

    /**
     * @param CommonName $commonName
     * @return void
     */
    public function rename(CommonName $commonName);

    /**
     * @param Email $email
     * @return void
     */
    public function changeEmail(Email $email);

    /**
     * @param SecondFactorId  $secondFactorId
     * @param YubikeyPublicId $yubikeyPublicId
     * @return void
     */
    public function bootstrapYubikeySecondFactor(
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId
    );

    /**
     * @param SecondFactorId          $secondFactorId
     * @param YubikeyPublicId         $yubikeyPublicId
     * @param EmailVerificationWindow $emailVerificationWindow
     * @return void
     */
    public function provePossessionOfYubikey(
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        EmailVerificationWindow $emailVerificationWindow
    );

    /**
     * @param SecondFactorId          $secondFactorId
     * @param PhoneNumber             $phoneNumber
     * @param EmailVerificationWindow $emailVerificationWindow
     * @return void
     */
    public function provePossessionOfPhone(
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        EmailVerificationWindow $emailVerificationWindow
    );

    /**
     * @param SecondFactorId          $secondFactorId
     * @param StepupProvider          $provider
     * @param GssfId                  $gssfId
     * @param EmailVerificationWindow $emailVerificationWindow
     * @return void
     */
    public function provePossessionOfGssf(
        SecondFactorId $secondFactorId,
        StepupProvider $provider,
        GssfId $gssfId,
        EmailVerificationWindow $emailVerificationWindow
    );

    /**
     * @param string $verificationNonce
     * @return void
     */
    public function verifyEmail($verificationNonce);

    /**
     * Attempts to vet another identity's verified second factor.
     *
     * @param Identity               $registrant
     * @param SecondFactorId         $registrantsSecondFactorId
     * @param SecondFactorType       $registrantsSecondFactorType
     * @param SecondFactorIdentifier $registrantsSecondFactorIdentifier
     * @param string                 $registrationCode
     * @param DocumentNumber         $documentNumber
     * @param bool                   $identityVerified
     * @return void
     * @throws DomainException
     */
    public function vetSecondFactor(
        Identity $registrant,
        SecondFactorId $registrantsSecondFactorId,
        SecondFactorType $registrantsSecondFactorType,
        SecondFactorIdentifier $registrantsSecondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber,
        $identityVerified
    );

    /**
     * Makes the identity comply with an authority's vetting of a verified second factor.
     *
     * @param SecondFactorId         $secondFactorId
     * @param SecondFactorType       $secondFactorType
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @param string                 $registrationCode
     * @param DocumentNumber         $documentNumber
     * @throws DomainException
     * @return void
     */
    public function complyWithVettingOfSecondFactor(
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        $registrationCode,
        DocumentNumber $documentNumber
    );

    /**
     * @param SecondFactorId $secondFactorId
     * @return void
     */
    public function revokeSecondFactor(SecondFactorId $secondFactorId);

    /**
     * @param SecondFactorId $secondFactorId
     * @param IdentityId $authorityId
     * @return void
     */
    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId);

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
    );

    /**
     * @param RegistrationAuthorityRole $role
     * @return void
     */
    public function appointAs(RegistrationAuthorityRole $role);

    /**
     * @param Location           $location
     * @param ContactInformation $contactInformation
     * @return void
     */
    public function amendRegistrationAuthorityInformation(Location $location, ContactInformation $contactInformation);

    /**
     * @return void
     */
    public function retractRegistrationAuthority();

    /**
     * @param Locale $preferredLocale
     * @return void
     */
    public function expressPreferredLocale(Locale $preferredLocale);

    /**
     * @return IdentityId
     */
    public function getId();

    /**
     * @return NameId
     */
    public function getNameId();

    /**
     * @return Institution
     */
    public function getInstitution();

    /**
     * @return CommonName
     */
    public function getCommonName();

    /**
     * @return Email
     */
    public function getEmail();

    /**
     * @return Locale
     */
    public function getPreferredLocale();

    /**
     * @param SecondFactorId $secondFactorId
     * @return VerifiedSecondFactor|null
     */
    public function getVerifiedSecondFactor(SecondFactorId $secondFactorId);
}

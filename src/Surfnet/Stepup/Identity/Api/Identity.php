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
use Surfnet\Stepup\IdentifyingData\Api\IdentifyingDataHolder;
use Surfnet\Stepup\IdentifyingData\Value\CommonName;
use Surfnet\Stepup\IdentifyingData\Value\Email;
use Surfnet\Stepup\Identity\Entity\VerifiedSecondFactor;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

interface Identity extends IdentifyingDataHolder, AggregateRoot
{
    /**
     * @param IdentityId  $id
     * @param Institution $institution
     * @param NameId      $nameId
     * @param Email       $email
     * @param CommonName  $commonName
     * @return Identity
     */
    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        Email $email,
        CommonName $commonName
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
     * @param SecondFactorId $secondFactorId
     * @param YubikeyPublicId $yubikeyPublicId
     * @return void
     */
    public function bootstrapYubikeySecondFactor(SecondFactorId $secondFactorId, YubikeyPublicId $yubikeyPublicId);

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
     * @param Identity       $registrant
     * @param SecondFactorId $registrantsSecondFactorId
     * @param string         $registrantsSecondFactorIdentifier
     * @param string         $registrationCode
     * @param string         $documentNumber
     * @param bool           $identityVerified
     * @return void
     * @throws DomainException
     */
    public function vetSecondFactor(
        Identity $registrant,
        SecondFactorId $registrantsSecondFactorId,
        $registrantsSecondFactorIdentifier,
        $registrationCode,
        $documentNumber,
        $identityVerified
    );

    /**
     * Makes the identity comply with an authority's vetting of a verified second factor.
     *
     * @param SecondFactorId $secondFactorId
     * @param string         $secondFactorIdentifier
     * @param string         $registrationCode
     * @param string         $documentNumber
     * @return void
     * @throws DomainException
     */
    public function complyWithVettingOfSecondFactor(
        SecondFactorId $secondFactorId,
        $secondFactorIdentifier,
        $registrationCode,
        $documentNumber
    );

    /**
     * @param SecondFactorId $secondFactorId
     * @return void
     */
    public function revokeSecondFactor(SecondFactorId $secondFactorId);

    /**
     * @param SecondFactorId $secondFactorId
     * @param IdentityId $authorityId
     * @return
     */
    public function complyWithSecondFactorRevocation(SecondFactorId $secondFactorId, IdentityId $authorityId);

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
     * @param SecondFactorId $secondFactorId
     * @return VerifiedSecondFactor|null
     */
    public function getVerifiedSecondFactor(SecondFactorId $secondFactorId);
}

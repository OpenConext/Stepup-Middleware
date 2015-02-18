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
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

interface Identity extends AggregateRoot
{
    /**
     * @param IdentityId  $id
     * @param Institution $institution
     * @param NameId      $nameId
     * @param string      $email
     * @param string      $commonName
     * @return Identity
     */
    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        $email,
        $commonName
    );

    /**
     * Construct a new aggregate root. Aggregate roots can only be affected by events, so no parameters are allowed.
     */
    public function __construct();

    /**
     * @param string $commonName
     * @return void
     */
    public function rename($commonName);

    /**
     * @param string $email
     * @return void
     */
    public function changeEmail($email);

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
     * @param string $verificationNonce
     * @return void
     */
    public function verifyEmail($verificationNonce);

    /**
     * @param string $registrationCode
     * @param string $secondFactorIdentifier
     * @param string $documentNumber
     * @param bool $identityVerified
     * @return void
     */
    public function vetSecondFactor($registrationCode, $secondFactorIdentifier, $documentNumber, $identityVerified);

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
     * @return string
     */
    public function getCommonName();

    /**
     * @return string
     */
    public function getEmail();
}

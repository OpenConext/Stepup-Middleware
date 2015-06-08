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

namespace Surfnet\Stepup\Identity\Event;

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class YubikeyPossessionProvenEvent extends IdentityEvent implements Forgettable
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * The Yubikey's public ID.
     *
     * @var \Surfnet\Stepup\Identity\Value\YubikeyPublicId
     */
    public $yubikeyPublicId;

    /**
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $emailVerificationRequestedAt;

    /**
     * @var \Surfnet\Stepup\Identity\Value\EmailVerificationWindow
     */
    public $emailVerificationWindow;

    /**
     * @var string
     */
    public $emailVerificationNonce;

    /**
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    public $commonName;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    public $email;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Locale Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId              $identityId
     * @param Institution             $institution
     * @param SecondFactorId          $secondFactorId
     * @param YubikeyPublicId         $yubikeyPublicId
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param string                  $emailVerificationNonce
     * @param CommonName              $commonName
     * @param Email                   $email
     * @param Locale                  $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        EmailVerificationWindow $emailVerificationWindow,
        $emailVerificationNonce,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $institution);

        $this->secondFactorId          = $secondFactorId;
        $this->yubikeyPublicId         = $yubikeyPublicId;
        $this->emailVerificationWindow = $emailVerificationWindow;
        $this->emailVerificationNonce  = $emailVerificationNonce;
        $this->commonName              = $commonName;
        $this->email                   = $email;
        $this->preferredLocale         = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;
        $metadata->secondFactorId         = $this->secondFactorId;
        $metadata->secondFactorType       = new SecondFactorType('yubikey');
        $metadata->secondFactorIdentifier = $this->yubikeyPublicId;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            YubikeyPublicId::unknown(),
            EmailVerificationWindow::deserialize($data['email_verification_window']),
            $data['email_verification_nonce'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
        );
    }

    public function serialize()
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'email_verification_window' => $this->emailVerificationWindow->serialize(),
            'email_verification_nonce'  => (string) $this->emailVerificationNonce,
            'preferred_locale'          => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->yubikeyPublicId, new SecondFactorType('yubikey'));
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email      = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $this->yubikeyPublicId = $sensitiveData->getSecondFactorIdentifier();
    }
}

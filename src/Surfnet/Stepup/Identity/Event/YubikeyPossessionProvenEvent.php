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
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class YubikeyPossessionProvenEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    protected static $whitelist = [
        'identity_id',
        'identity_institution',
        'second_factor_id',
        'preferred_locale',
        'second_factor_identifier',
        'second_factor_type',
        'email',
        'common_name',
    ];

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
     * @var bool
     */
    public $emailVerificationRequired;

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
     * @param bool                    $emailVerificationRequired
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param string                  $emailVerificationNonce
     * @param CommonName              $commonName
     * @param Email                   $email
     * @param Locale                  $preferredLocale
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        $emailVerificationNonce,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $institution);

        $this->secondFactorId            = $secondFactorId;
        $this->yubikeyPublicId           = $yubikeyPublicId;
        $this->emailVerificationRequired = $emailVerificationRequired;
        $this->emailVerificationWindow   = $emailVerificationWindow;
        $this->emailVerificationNonce    = $emailVerificationNonce;
        $this->commonName                = $commonName;
        $this->email                     = $email;
        $this->preferredLocale           = $preferredLocale;
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
        if (!isset($data['email_verification_required'])) {
            $data['email_verification_required'] = true;
        }

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            YubikeyPublicId::unknown(),
            $data['email_verification_required'],
            EmailVerificationWindow::deserialize($data['email_verification_window']),
            $data['email_verification_nonce'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'identity_id'                 => (string) $this->identityId,
            'identity_institution'        => (string) $this->identityInstitution,
            'second_factor_id'            => (string) $this->secondFactorId,
            'email_verification_required' => (bool) $this->emailVerificationRequired,
            'email_verification_window'   => $this->emailVerificationWindow->serialize(),
            'email_verification_nonce'    => (string) $this->emailVerificationNonce,
            'preferred_locale'            => (string) $this->preferredLocale,
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

    public function obtainUserData(): array
    {
        $serializedPublicUserData = $this->serialize();
        $serializedSensitiveUserData = $this->getSensitiveData()->serialize();
        $serializedCombinedUserData = array_merge($serializedPublicUserData, $serializedSensitiveUserData);
        $whitelist = array_flip(self::$whitelist);
        return array_intersect_key($serializedCombinedUserData, $whitelist);
    }
}

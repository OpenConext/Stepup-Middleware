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
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class GssfPossessionProvenEvent extends IdentityEvent implements Forgettable
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\StepupProvider
     */
    public $stepupProvider;

    /**
     * @var \Surfnet\Stepup\Identity\Value\GssfId
     */
    public $gssfId;

    /**
     * @var bool
     */
    public $emailVerificationRequired;

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
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param IdentityId              $identityId
     * @param Institution             $identityInstitution
     * @param SecondFactorId          $secondFactorId
     * @param StepupProvider          $stepupProvider
     * @param GssfId                  $gssfId
     * @param bool                    $emailVerificationRequired
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param string                  $emailVerificationNonce
     * @param CommonName              $commonName
     * @param Email                   $email
     * @param Locale                  $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        StepupProvider $stepupProvider,
        GssfId $gssfId,
        $emailVerificationRequired,
        EmailVerificationWindow $emailVerificationWindow,
        $emailVerificationNonce,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId            = $secondFactorId;
        $this->stepupProvider            = $stepupProvider;
        $this->gssfId                    = $gssfId;
        $this->emailVerificationRequired = $emailVerificationRequired;
        $this->emailVerificationWindow   = $emailVerificationWindow;
        $this->emailVerificationNonce    = $emailVerificationNonce;
        $this->commonName                = $commonName;
        $this->email                     = $email;
        $this->preferredLocale           = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType((string) $this->stepupProvider);
        $metadata->secondFactorIdentifier = $this->gssfId;

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
            new StepupProvider($data['stepup_provider']),
            GssfId::unknown(),
            $data['email_verification_required'],
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
            'identity_id'                 => (string) $this->identityId,
            'identity_institution'        => (string) $this->identityInstitution,
            'second_factor_id'            => (string) $this->secondFactorId,
            'stepup_provider'             => (string) $this->stepupProvider,
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
            ->withSecondFactorIdentifier($this->gssfId, new SecondFactorType((string) $this->stepupProvider));
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email      = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $this->gssfId     = $sensitiveData->getSecondFactorIdentifier();
    }
}

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

use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\StepupBundle\Value\SecondFactorType;

class GssfPossessionProvenEvent extends IdentityEvent
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
     * @var \Surfnet\Stepup\Identity\Value\EmailVerificationWindow
     */
    public $emailVerificationWindow;

    /**
     * @var IdentifyingDataId
     */
    public $identifyingDataId;

    /**
     * @var string
     */
    public $emailVerificationNonce;

    /**
     * @var Locale Eg. "en_GB"
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
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param IdentifyingDataId       $identifyingDataId
     * @param string                  $emailVerificationNonce
     * @param Locale                  $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        StepupProvider $stepupProvider,
        GssfId $gssfId,
        EmailVerificationWindow $emailVerificationWindow,
        IdentifyingDataId $identifyingDataId,
        $emailVerificationNonce,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId          = $secondFactorId;
        $this->stepupProvider          = $stepupProvider;
        $this->gssfId                  = $gssfId;
        $this->emailVerificationWindow = $emailVerificationWindow;
        $this->identifyingDataId       = $identifyingDataId;
        $this->emailVerificationNonce  = $emailVerificationNonce;
        $this->preferredLocale         = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType((string) $this->stepupProvider);
        $metadata->secondFactorIdentifier = (string) $this->gssfId;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new StepupProvider($data['stepup_provider']),
            new GssfId($data['gssf_id']),
            EmailVerificationWindow::deserialize($data['email_verification_window']),
            new IdentifyingDataId($data['identifying_data_id']),
            $data['email_verification_nonce'],
            new Locale($data['preferred_locale'])
        );
    }

    public function serialize()
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'stepup_provider'           => (string) $this->stepupProvider,
            'gssf_id'                   => (string) $this->gssfId,
            'email_verification_window' => $this->emailVerificationWindow->serialize(),
            'identifying_data_id'       => (string) $this->identifyingDataId,
            'email_verification_nonce'  => (string) $this->emailVerificationNonce,
            'preferred_locale'          => (string) $this->preferredLocale,
        ];
    }
}

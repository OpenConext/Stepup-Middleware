<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;

class NewInstitutionConfigurationCreatedEvent implements SerializableInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    public $institutionConfigurationId;

    /**
     * @var Institution
     */
    public $institution;
    /**
     * @var UseRaLocationsOption
     */
    public $useRaLocationsOption;

    /**
     * @var ShowRaaContactInformationOption
     */
    public $showRaaContactInformationOption;

    /**
     * @var VerifyEmailOption
     */
    public $verifyEmailOption;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    public $numberOfTokensPerIdentityOption;

    public function __construct(
        InstitutionConfigurationId $institutionConfigurationId,
        Institution $institution,
        UseRaLocationsOption $useRaLocationsOption,
        ShowRaaContactInformationOption $showRaaContactInformationOption,
        VerifyEmailOption $verifyEmailOption,
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption
    ) {
        $this->institutionConfigurationId      = $institutionConfigurationId;
        $this->institution                     = $institution;
        $this->useRaLocationsOption            = $useRaLocationsOption;
        $this->showRaaContactInformationOption = $showRaaContactInformationOption;
        $this->verifyEmailOption               = $verifyEmailOption;
        $this->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption;
    }

    public static function deserialize(array $data)
    {
        if (!isset($data['verify_email_option'])) {
            $data['verify_email_option'] = true;
        }
        if (!isset($data['number_of_tokens_per_identity_option'])) {
            $data['number_of_tokens_per_identity_option'] = NumberOfTokensPerIdentityOption::DISABLED;
        }

        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new Institution($data['institution']),
            new UseRaLocationsOption($data['use_ra_locations_option']),
            new ShowRaaContactInformationOption($data['show_raa_contact_information_option']),
            new VerifyEmailOption($data['verify_email_option']),
            new NumberOfTokensPerIdentityOption($data['number_of_tokens_per_identity_option'])
        );
    }

    public function serialize()
    {
        return [
            'institution_configuration_id'        => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'institution'                         => $this->institution->getInstitution(),
            'use_ra_locations_option'             => $this->useRaLocationsOption->isEnabled(),
            'show_raa_contact_information_option' => $this->showRaaContactInformationOption->isEnabled(),
            'verify_email_option'                 => $this->verifyEmailOption->isEnabled(),
            'number_of_tokens_per_identity_option' => $this->numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity(),
        ];
    }
}

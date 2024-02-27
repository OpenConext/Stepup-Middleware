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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

#[ORM\Entity(repositoryClass: InstitutionConfigurationOptionsRepository::class)]
class InstitutionConfigurationOptions
{
    /**
     *
     * @var Institution
     */
    #[ORM\Id]
    #[ORM\Column(type: 'stepup_configuration_institution')]
    public $institution;

    /**
     * @var UseRaLocationsOption
     */
    #[ORM\Column(type: 'stepup_use_ra_locations_option')]
    public $useRaLocationsOption;

    /**
     * @var ShowRaaContactInformationOption
     */
    #[ORM\Column(type: 'stepup_show_raa_contact_information_option')]
    public $showRaaContactInformationOption;

    /**
     * @var VerifyEmailOption
     */
    #[ORM\Column(type: 'stepup_verify_email_option', options: ['default' => 1])]
    public $verifyEmailOption;

    /**
     * @var SelfVetOption
     */
    #[ORM\Column(type: 'stepup_self_vet_option', options: ['default' => 0])]
    public $selfVetOption;

    /**
     * @var SsoOn2FaOption
     */
    #[ORM\Column(type: 'stepup_sso_on_2fa_option', options: ['default' => 0])]
    public $ssoOn2faOption;

    /**
     * @var SelfAssertedTokensOption
     */
    #[ORM\Column(type: 'stepup_self_asserted_tokens_option', options: ['default' => 0])]
    public $selfAssertedTokensOption;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    #[ORM\Column(type: 'stepup_number_of_tokens_per_identity_option', options: ['default' => 0])]
    public $numberOfTokensPerIdentityOption;

    public static function create(
        Institution $institution,
        UseRaLocationsOption $useRaLocationsOption,
        ShowRaaContactInformationOption $showRaaContactInformationOption,
        VerifyEmailOption $verifyEmailOption,
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption,
        SsoOn2faOption $ssoOn2faOption,
        SelfVetOption $selfVetOption,
        SelfAssertedTokensOption $selfAssertedTokensOption
    ): self {
        $options = new self;

        $options->institution                     = $institution;
        $options->useRaLocationsOption            = $useRaLocationsOption;
        $options->showRaaContactInformationOption = $showRaaContactInformationOption;
        $options->verifyEmailOption               = $verifyEmailOption;
        $options->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption;
        $options->ssoOn2faOption = $ssoOn2faOption;
        $options->selfVetOption = $selfVetOption;
        $options->selfAssertedTokensOption = $selfAssertedTokensOption;

        return $options;
    }
}

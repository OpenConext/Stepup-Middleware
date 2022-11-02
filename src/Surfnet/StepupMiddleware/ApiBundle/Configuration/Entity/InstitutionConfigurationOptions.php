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

/**
 * @ORM\Entity(
 *      repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository"
 * )
 */
class InstitutionConfigurationOptions
{
    /**
     * @ORM\Id
     * @ORM\Column(type="stepup_configuration_institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Column(type="stepup_use_ra_locations_option")
     *
     * @var UseRaLocationsOption
     */
    public $useRaLocationsOption;

    /**
     * @ORM\Column(type="stepup_show_raa_contact_information_option")
     *
     * @var ShowRaaContactInformationOption
     */
    public $showRaaContactInformationOption;

    /**
     * @ORM\Column(type="stepup_verify_email_option", options={"default" : 1})
     *
     * @var VerifyEmailOption
     */
    public $verifyEmailOption;

    /**
     * @ORM\Column(type="stepup_self_vet_option", options={"default" : 0})
     *
     * @var SelfVetOption
     */
    public $selfVetOption;

    /**
     * @ORM\Column(type="stepup_sso_on_2fa_option", options={"default" : 0})
     *
     * @var SsoOn2FaOption
     */
    public $ssoOn2faOption;

    /**
     * @ORM\Column(type="stepup_self_asserted_tokens_option", options={"default" : 0})
     *
     * @var SelfAssertedTokensOption
     */
    public $selfAssertedTokensOption;

    /**
     * @ORM\Column(type="stepup_number_of_tokens_per_identity_option", options={"default" : 0})
     *
     * @var NumberOfTokensPerIdentityOption
     */
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
    ) {
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

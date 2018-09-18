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
use Surfnet\Stepup\Configuration\Value\SelectRaaOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaaOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\UseRaOption;
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
     * @ORM\Column(type="stepup_number_of_tokens_per_identity_option", options={"default" : 0})
     *
     * @var NumberOfTokensPerIdentityOption
     */
    public $numberOfTokensPerIdentityOption;

    /**
     * @ORM\Column(type="stepup_use_ra_option", nullable=true)
     *
     * @var UseRaOption
     */
    public $useRaOption;

    /**
     * @ORM\Column(type="stepup_use_raa_option", nullable=true)
     *
     * @var UseRaaOption
     */
    public $useRaaOption;

    /**
     * @ORM\Column(type="stepup_select_raa_option", nullable=true)
     *
     * @var SelectRaaOption
     */
    public $selectRaaOption;

    public static function create(
        Institution $institution,
        UseRaLocationsOption $useRaLocationsOption,
        ShowRaaContactInformationOption $showRaaContactInformationOption,
        VerifyEmailOption $verifyEmailOption,
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption,
        UseRaOption $useRaOption,
        UseRaaOption $useRaaOption,
        SelectRaaOption $selectRaaOption
    ) {
        $options = new self;

        $options->institution                     = $institution;
        $options->useRaLocationsOption            = $useRaLocationsOption;
        $options->showRaaContactInformationOption = $showRaaContactInformationOption;
        $options->verifyEmailOption               = $verifyEmailOption;
        $options->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption;

        $options->useRaOption = $useRaOption;
        $options->useRaaOption = $useRaaOption;
        $options->selectRaaOption = $selectRaaOption;

        return $options;
    }
}

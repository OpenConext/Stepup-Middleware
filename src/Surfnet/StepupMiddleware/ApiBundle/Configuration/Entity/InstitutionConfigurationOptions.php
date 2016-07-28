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
use JsonSerializable;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;

/**
 * @ORM\Entity(
 *      repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository"
 * )
 */
final class InstitutionConfigurationOptions implements JsonSerializable
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

    public static function create(
        Institution $institution,
        UseRaLocationsOption $useRaLocationsOption,
        ShowRaaContactInformationOption $showRaaContactInformationOption
    ) {
        $options = new self;

        $options->institution                     = $institution;
        $options->useRaLocationsOption            = $useRaLocationsOption;
        $options->showRaaContactInformationOption = $showRaaContactInformationOption;

        return $options;
    }

    public function jsonSerialize()
    {
        return [
            'institution' => $this->institution,
            'use_ra_locations' => $this->useRaLocationsOption,
            'show_raa_contact_information' => $this->showRaaContactInformationOption,
        ];
    }
}

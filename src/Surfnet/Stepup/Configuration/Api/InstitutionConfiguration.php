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

namespace Surfnet\Stepup\Configuration\Api;

use Broadway\Domain\AggregateRoot;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;

interface InstitutionConfiguration extends AggregateRoot
{
    public static function create(InstitutionConfigurationId $institutionConfigurationId, Institution $institution): InstitutionConfiguration;

    public function configureUseRaLocationsOption(UseRaLocationsOption $useRaLocationsOption): void;

    public function configureShowRaaContactInformationOption(
        ShowRaaContactInformationOption $showRaaContactInformationOption,
    ): void;

    public function addRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation,
    ): void;

    public function changeRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation,
    ): void;

    public function removeRaLocation(RaLocationId $raLocationId): void;

    /**
     * @return void
     */
    public function destroy(): void;
}

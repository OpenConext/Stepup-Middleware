<?php

declare(strict_types=1);

/**
 * Copyright 2022 SURF B.V.
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

use Broadway\Serializer\Serializable as SerializableInterface;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;

final class SsoOn2faOptionChangedEvent implements SerializableInterface
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
     * @var SsoOn2faOption
     */
    public $ssoOn2faOption;

    public function __construct(
        InstitutionConfigurationId $institutionConfigurationId,
        Institution $institution,
        SsoOn2faOption $ssoOn2faOption
    ) {
        $this->institutionConfigurationId = $institutionConfigurationId;
        $this->institution = $institution;
        $this->ssoOn2faOption = $ssoOn2faOption;
    }

    public static function deserialize(array $data): self
    {
        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new Institution($data['institution']),
            new SsoOn2faOption($data['sso_on_2fa_option'])
        );
    }

    public function serialize(): array
    {
        return [
            'institution_configuration_id' => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'institution' => $this->institution->getInstitution(),
            'sso_on_2fa_option' => $this->ssoOn2faOption->isEnabled(),
        ];
    }
}

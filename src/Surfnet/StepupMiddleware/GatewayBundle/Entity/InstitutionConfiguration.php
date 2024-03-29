<?php

declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\GatewayBundle\Repository\InstitutionConfigurationRepository")
 */
class InstitutionConfiguration
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(length=200)
     */
    public $institution;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool is the SSO on 2FA feature enabled?
     */
    public $ssoOn2faEnabled;

    public function __construct(string $institution, bool $ssoOn2faEnabled)
    {
        $this->institution = $institution;
        $this->ssoOn2faEnabled = $ssoOn2faEnabled;
    }
}

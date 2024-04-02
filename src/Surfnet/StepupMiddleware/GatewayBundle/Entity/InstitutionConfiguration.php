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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\InstitutionConfigurationRepository;

#[ORM\Entity(repositoryClass: InstitutionConfigurationRepository::class)]
class InstitutionConfiguration
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 200)]
        public string $institution,
        /**
         * @var bool is the SSO on 2FA feature enabled?
         */
        #[ORM\Column(type: 'boolean')]
        public bool $ssoOn2faEnabled
    ) {
    }
}

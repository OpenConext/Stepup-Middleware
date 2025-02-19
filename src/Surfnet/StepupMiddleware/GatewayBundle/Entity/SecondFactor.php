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

namespace Surfnet\StepupMiddleware\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\SecondFactorRepository;

/**
 * WARNING: Any schema change made to this entity should also be applied to the Gateway SecondFactor entity!
 * @see Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor (in OpenConext/Stepup-Gateway project)
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 */
#[ORM\Table]
#[ORM\Index(name: 'idx_secondfactor_nameid', columns: ['name_id'])]
#[ORM\Entity(repositoryClass: SecondFactorRepository::class)]
class SecondFactor
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 36)]
        private string $identityId,
        #[ORM\Column(length: 200)]
        private string $nameId,
        #[ORM\Column(length: 200)]
        private string $institution,
        /**
         * In which language to display any second factor verification screens.
         */
        #[ORM\Column]
        public string $displayLocale,
        #[ORM\Column(length: 36)]
        private string $secondFactorId,
        #[ORM\Column(length: 255)]
        private string $secondFactorIdentifier,
        #[ORM\Column(length: 50)]
        private string $secondFactorType,
        /**
         * This boolean indicates if the second factor token was vetted
         * using one of the vetting types that are considered 'identity-vetted'.
         * That in turn means if the owner of the second factor token has its
         * identity vetted (verified) by a RA(A) at the service desk. This trickles
         * down to the self-vet vetting type. As the token used for self vetting
         * was RA vetted.
         */
        #[ORM\Column(type: 'boolean', options: ['default' => '1'])]
        private bool $identityVetted,
    ) {
        $this->id = (string)Uuid::uuid4();
    }
}

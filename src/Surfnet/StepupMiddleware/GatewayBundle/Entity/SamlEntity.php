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

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'unq_saml_entity_entity_id_type', columns: ['entity_id', 'type'])]
#[ORM\Entity(repositoryClass: SamlEntityRepository::class)]
class SamlEntity
{
    /**
     * Constants denoting the type of SamlEntity. Also used in the gateway to make that distinction
     */
    public const TYPE_IDP = 'idp';
    public const TYPE_SP = 'sp';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public $id;

    private function __construct(
        #[ORM\Column]
        public string $entityId,
        #[ORM\Column]
        public string $type,
        #[ORM\Column(type: 'text')]
        public string $configuration,
    ) {
        $this->id = (string)Uuid::uuid4();
    }

    /**
     * @param string $entityId
     * @return SamlEntity
     */
    public static function createServiceProvider($entityId, array $configuration): self
    {
        return new self($entityId, self::TYPE_SP, json_encode($configuration));
    }

    /**
     * @param string $entityId
     * @return SamlEntity
     */
    public static function createIdentityProvider($entityId, array $configuration): self
    {
        return new self($entityId, self::TYPE_IDP, json_encode($configuration));
    }
}

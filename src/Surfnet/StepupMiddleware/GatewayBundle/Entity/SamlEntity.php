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
    const TYPE_IDP = 'idp';
    const TYPE_SP  = 'sp';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public $id;

    /**
     * @var string
     */
    #[ORM\Column]
    public $entityId;

    /**
     * @var string
     */
    #[ORM\Column]
    public $type;

    /**
     * @var string the configuration as json string
     */
    #[ORM\Column(type: 'text')]
    public $configuration;

    /**
     * @param string $entityId
     * @param string $type
     * @param string $configuration
     */
    private function __construct($entityId, $type, $configuration)
    {
        $this->id = (string) Uuid::uuid4();
        $this->entityId = $entityId;
        $this->type = $type;
        $this->configuration = $configuration;
    }

    /**
     * @param string $entityId
     * @param array  $configuration
     * @return SamlEntity
     */
    public static function createServiceProvider($entityId, array $configuration)
    {
        return new self($entityId, self::TYPE_SP, json_encode($configuration));
    }

    /**
     * @param string $entityId
     * @param array  $configuration
     * @return SamlEntity
     */
    public static function createIdentityProvider($entityId, array $configuration)
    {
        return new self($entityId, self::TYPE_IDP, json_encode($configuration));
    }
}

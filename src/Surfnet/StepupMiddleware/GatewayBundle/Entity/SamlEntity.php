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

use Assert\Assertion as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\GatewayBundle\Entity\SamlEntityRepository")
 * @ORM\Table()
 */
class SamlEntity
{
    const TYPE_IDP = 'idp';
    const TYPE_SP  = 'sp';

    /**
     * @ORM\Id
     * @ORM\Column
     *
     * @var string
     */
    public $entityId;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $type;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    public $configuration;

    private function __construct($entityId, $type, $configuration)
    {
        $this->entityId = $entityId;
        $this->type = $type;
        $this->configuration = $configuration;
    }

    public static function createServiceProvider($entityId, array $configuration)
    {
        return new self($entityId, self::TYPE_SP, json_encode($configuration));
    }
}

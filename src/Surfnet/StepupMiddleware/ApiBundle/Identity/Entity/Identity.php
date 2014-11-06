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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository")
 */
class Identity implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $nameId;

    public function __construct($id, $nameId)
    {
        if (!is_string($id)) {
            throw InvalidArgumentException::invalidType('string', 'id', $id);
        }

        if (!is_string($nameId)) {
            throw InvalidArgumentException::invalidType('string', 'nameId', $nameId);
        }

        $this->id = $id;
        $this->nameId = $nameId;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNameId()
    {
        return $this->nameId;
    }

    public function jsonSerialize()
    {
        return ['id' => $this->id, 'name_id' => $this->nameId];
    }
}

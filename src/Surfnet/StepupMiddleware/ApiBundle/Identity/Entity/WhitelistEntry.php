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
use Surfnet\Stepup\Identity\Value\Institution;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Repository\WhitelistEntryRepository")
 */
class WhitelistEntry implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="institution")
     *
     * @var Institution
     */
    public $institution;

    public static function createFrom(Institution $institution)
    {
        $instance              = new self();
        $instance->institution = $institution;

        return $instance;
    }

    public function jsonSerialize()
    {

    }
}

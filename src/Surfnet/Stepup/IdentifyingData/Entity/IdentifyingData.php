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

namespace Surfnet\Stepup\IdentifyingData\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\Stepup\IdentifyingData\Value\CommonName;
use Surfnet\Stepup\IdentifyingData\Value\Email;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\Value\IdentityId;

/**
 * @ORM\Entity(repositoryClass="Surfnet\Stepup\IdentifyingData\Entity\IdentifyingDataRepository")
 * @ORM\Table(name="identity_identifying_data")
 */
class IdentifyingData
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var IdentifyingDataId
     */
    public $id;

    /**
     * @ORM\Column(type="stepup_common_name", length=255)
     *
     * @var CommonName
     */
    public $commonName;

    /**
     * @ORM\Column(type="stepup_email", length=255)
     *
     * @var Email
     */
    public $email;

    public static function createFrom(IdentityId $id, Email $email, CommonName $commonName)
    {
        $identifyingData             = new self();
        $identifyingData->id         = IdentifyingDataId::fromIdentityId($id);
        $identifyingData->email      = $email;
        $identifyingData->commonName = $commonName;

        return $identifyingData;
    }
}

<?php

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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

/**
 * @ORM\Entity(
 *     repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository"
 * )
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_recovery_method_type", columns={"type"}),
 *     }
 * )
 */
class RecoveryToken implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $identityId;

    /**
     * @ORM\Column(length=16)
     *
     * @var string
     */
    public $type;

    /**
     * @ORM\Column(type="stepup_recovery_token_status")
     *
     * @var RecoveryTokenStatus
     */
    public $status;

    /**
     * @ORM\Column(type="institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * The name of the registrant.
     *
     * @ORM\Column(type="stepup_common_name")
     *
     * @var CommonName
     */
    public $name;

    /**
     * The e-mail of the registrant.
     *
     * @ORM\Column(type="stepup_email")
     *
     * @var Email
     */
    public $email;

    /**
     * @ORM\Column(length=255)
     *
     * @var string
     */
    public $recoveryMethodIdentifier;

    public function jsonSerialize()
    {
        return [
            'id'   => $this->id,
            'type' => $this->type,
            'status' => (string) $this->status,
            'recovery_method_identifier' => $this->recoveryMethodIdentifier,
            'identity_id' => $this->identityId,
            'name' => $this->name,
            'email' => $this->email,
            'institution' => $this->institution,
        ];
    }
}

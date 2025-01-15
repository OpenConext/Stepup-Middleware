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
use JsonSerializable;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

#[ORM\Table]
#[ORM\Index(name: 'idx_recovery_method_type', columns: ['type'])]
#[ORM\Entity(repositoryClass: RecoveryTokenRepository::class)]
class RecoveryToken implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    /**
     * @var string
     */
    #[ORM\Column(length: 36)]
    public string $identityId;

    /**
     * @var string
     */
    #[ORM\Column(length: 16)]
    public string $type;

    /**
     * @var RecoveryTokenStatus
     */
    #[ORM\Column(type: 'stepup_recovery_token_status')]
    public RecoveryTokenStatus $status;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'institution')]
    public Institution $institution;

    /**
     * The name of the registrant.
     * @var CommonName
     */
    #[ORM\Column(type: 'stepup_common_name')]
    public CommonName $name;

    /**
     * The e-mail of the registrant.
     * @var Email
     */
    #[ORM\Column(type: 'stepup_email')]
    public Email $email;

    /**
     * @var string
     */
    #[ORM\Column(length: 255)]
    public string $recoveryMethodIdentifier;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => (string)$this->status,
            'recovery_method_identifier' => $this->recoveryMethodIdentifier,
            'identity_id' => $this->identityId,
            'name' => $this->name,
            'email' => $this->email,
            'institution' => $this->institution,
        ];
    }
}

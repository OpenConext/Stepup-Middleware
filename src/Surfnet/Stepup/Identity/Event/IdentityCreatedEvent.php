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

namespace Surfnet\Stepup\Identity\Event;

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;

class IdentityCreatedEvent extends IdentityEvent
{
    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var Institution
     */
    public $institution;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $commonName;

    public function __construct(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        $email,
        $commonName
    ) {
        parent::__construct($id);

        $this->institution = $institution;
        $this->nameId = $nameId;
        $this->email = $email;
        $this->commonName = $commonName;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
            $data['email'],
            $data['common_name']
        );
    }

    public function serialize()
    {
        return [
            'id' => (string) $this->identityId,
            'institution' => (string) $this->institution,
            'name_id' => (string) $this->nameId,
            'email' => $this->email,
            'common_name' => $this->commonName
        ];
    }
}

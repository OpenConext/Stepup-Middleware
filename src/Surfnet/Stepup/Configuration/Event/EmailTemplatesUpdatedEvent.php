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

namespace Surfnet\Stepup\Configuration\Event;

class EmailTemplatesUpdatedEvent extends ConfigurationEvent
{
    /**
     * @var array
     */
    public array $emailTemplates;

    /**
     * @param string $configurationId
     * @param array $emailTemplates
     */
    public function __construct($configurationId, array $emailTemplates)
    {
        parent::__construct($configurationId);

        $this->emailTemplates = $emailTemplates;
    }

    public static function deserialize(array $data): self
    {
        return new self($data['id'], $data['email_templates']);
    }

    public function serialize(): array
    {
        return ['id' => $this->id, 'email_templates' => $this->emailTemplates];
    }
}

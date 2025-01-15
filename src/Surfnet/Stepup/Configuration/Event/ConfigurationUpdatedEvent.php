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

class ConfigurationUpdatedEvent extends ConfigurationEvent
{
    /**
     * @param string $id
     * @param array $newConfiguration
     * @param array|null $oldConfiguration
     */
    public function __construct($id, public array $newConfiguration, public ?array $oldConfiguration = null)
    {
        parent::__construct($id);
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['id'],
            $data['new_configuration'],
            $data['old_configuration'],
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'new_configuration' => $this->newConfiguration,
            'old_configuration' => $this->oldConfiguration,
        ];
    }
}

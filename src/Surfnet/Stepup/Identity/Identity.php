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

namespace Surfnet\Stepup\Identity;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;

class Identity extends EventSourcedAggregateRoot implements IdentityApi
{
    /**
     * @var IdentityId
     */
    private $id;

    /**
     * @var NameId
     */
    private $nameId;

    public static function create(IdentityId $id, NameId $nameId)
    {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $nameId));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->nameId = $event->nameId;
    }

    /**
     * @return string
     */
    public function getAggregateRootId()
    {
        return (string) $this->id;
    }
}

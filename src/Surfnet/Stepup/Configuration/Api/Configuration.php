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

namespace Surfnet\Stepup\Configuration\Api;

use Broadway\Domain\AggregateRoot;

interface Configuration extends AggregateRoot
{
    /**
     * @return Configuration
     */
    public static function create();

    /**
     * @param string $newConfiguration
     * @return void
     */
    public function update($newConfiguration);

    /**
     * Used to be able to update the gateway configuration within a single transaction.
     *
     * @return null|\Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent
     */
    public function getLastUncommittedServiceProvidersUpdatedEvent();

    /**
     * Used to be able to update the raa configuration within a single transaction.
     *
     * @return null|\Surfnet\Stepup\Configuration\Event\RaaUpdatedEvent
     */
    public function getLastUncommittedRaaUpdatedEvent();

    /**
     * Used to be able to update the raa configuration within a single transaction.
     *
     * @return null|\Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent
     */
    public function getLastUncommittedSraaUpdatedEvent();
}

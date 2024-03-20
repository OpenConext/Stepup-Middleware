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

namespace Surfnet\Stepup\Identity\Api;

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;

interface Whitelist
{
    /**
     * Instantiate a new Whitelist, should not be used.
     */
    public function __construct();

    /**
     * Create a new Whitelist instance with the institutions that are on the initial whitelist
     */
    public static function create(InstitutionCollection $institutionCollection): Whitelist;

    /**
     * Replace all institutions on the whitelist with the institutions in the given collection
     */
    public function replaceAll(InstitutionCollection $institutionCollection): void;

    /**
     * Add the institutions in the given collection to the whitelist
     */
    public function add(InstitutionCollection $institutionCollection): void;

    /**
     * Remove the institutions in the given collection from the whitelist
     */
    public function remove(InstitutionCollection $institutionCollection): void;
}

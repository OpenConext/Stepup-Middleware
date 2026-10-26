<?php

/**
 * Copyright 2026 SURFnet bv
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

return [
    'expectedPropertyPath' => 'gateway.service_providers[0].service_name',
    'configuration' => [
        'gateway' => [
            'identity_providers' => [],
            'service_providers' => [
                [
                    "entity_id" => "https://entity.tld/id",
                    "public_key" => "MIIE...",
                    "acs" => ["https://entity.tld/consume-assertion"],
                    "loa" => [
                        "__default__" => "https://entity.tld/authentication/loa2",
                    ],
                    "second_factor_only" => false,
                    "second_factor_only_nameid_patterns" => [],
                    "assertion_encryption_enabled" => false,
                    "blacklisted_encryption_algorithms" => [],
                    // en_GB and en_US both normalize to the primary subtag "en". Gateway
                    // matches on the primary subtag only, so this is rejected: which entry
                    // "wins" would otherwise be an undocumented JSON-key-order accident.
                    "service_name" => [
                        'en_GB' => 'English (UK) Name',
                        'en_US' => 'English (US) Name',
                    ],
                ],
            ],
        ],
    ],
];

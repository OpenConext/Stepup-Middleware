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

return [
    'expectedPropertyPath' => 'sraa',
    'configuration' => [
        'gateway' => [
            'identity_providers' => [],
            'service_providers' => [
                [
                    'entity_id' => 'http://entity.tld/metadata',
                    'public_key' => 'MIIEEE...',
                    'acs' => ['http://entity.tld/consume-assertion'],
                    'loa' => [
                        '__default__' => 'http://gateway.tld/loa/1',
                    ],
                    "second_factor_only" => false,
                    "second_factor_only_nameid_patterns" => [],
                    "assertion_encryption_enabled" => false,
                    "blacklisted_encryption_algorithms" => [],
                ],
            ],
        ],
        'sraa' => null,
        'email_templates' => [],
    ],
];

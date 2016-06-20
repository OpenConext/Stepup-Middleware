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
    'expectedPropertyPath' => 'gateway',
    'configuration' => [
        'gateway' => [
            'identity_providers' => [],
        ],
        'sraa' => ['20394-4320423-439248324'],
        'email_templates' => [
            'confirm_email'     => ['en_GB' => 'Verify {{ commonName }}'],
            'registration_code' => ['en_GB' => 'Code {{ commonName }}'],
        ],
        'institutions_with_personal_ra_details' => ['institution.test'],
    ]
];

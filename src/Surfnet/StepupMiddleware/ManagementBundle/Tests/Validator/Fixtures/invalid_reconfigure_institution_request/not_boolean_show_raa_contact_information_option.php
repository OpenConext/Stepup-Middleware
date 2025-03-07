<?php

/**
 * Copyright 2016 SURFnet B.V.
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
    'expectedPropertyPath' => 'Institution(surfnet.nl)',
    'expectErrorMessageToContain' => 'must be a boolean',
    'reconfigureInstitutionRequest' => [
        'surfnet.nl' => [
            'use_ra_locations' => 1,
            'show_raa_contact_information' => true,
            'verify_email' => false,
            'sso_on_2fa' => false,
            'self_vet' => false,
            'allow_self_asserted_tokens' => false,
            'number_of_tokens_per_identity' => 1,
            'allowed_second_factors' => [],
        ],
    ],
];

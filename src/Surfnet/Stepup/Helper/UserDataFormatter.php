<?php
/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\Stepup\Helper;

class UserDataFormatter implements UserDataFormatterInterface
{
    public function __construct(private readonly string $applicationName)
    {
    }

    public function format(array $userData, array $errors): array
    {
        $data = [];
        foreach ($userData as $name => $event) {
            $name = explode('-', (string) $name)[1];
            $data[] = [
                'name' => $name,
                'value' => $event,
            ];
        }
        return $this->formatResponse($data, $errors);
    }

    private function formatResponse(array $userData, array $errors): array
    {
        $status = 'OK';
        $data = [
            'name' => $this->applicationName,
            'data' => $userData,
        ];

        if ($errors !== []) {
            $data['message'] = $errors;
            $status = 'FAILED';
        }

        $data['status'] = $status;

        return $data;
    }
}

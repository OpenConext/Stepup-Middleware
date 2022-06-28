<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Response;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonAuthorizationResponse;

class JsonAuthorizationResponseTest extends TestCase
{
    public function test_happy_flow() {
        $response = new JsonAuthorizationResponse(200);
        $this->assertEquals('{"code":200}',$response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_happy_flow_error_response() {
        $response = new JsonAuthorizationResponse(403);
        $this->assertEquals('{"code":403}',$response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_happy_flow_error_response_with_error_message() {
        $response = new JsonAuthorizationResponse(403, ['Not allowed']);
        $this->assertEquals('{"code":403,"errors":["Not allowed"]}',$response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_response_code_can_be_one_of_200_or_403() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The status code can be either 200 or 403');
        new JsonAuthorizationResponse(402);
    }

    public function test_all_errors_should_be_string() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The error messages should all be strings');
        new JsonAuthorizationResponse(403, ['Test', false]);
    }
}

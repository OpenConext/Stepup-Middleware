<?php

/**
 * Copyright 2024 SURFnet bv
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

declare(strict_types=1);

namespace Surfnet\Stepup\MigrationsFactory;

use Surfnet\Stepup\Exception\RuntimeException;

trait ConfigurationAwareMigrationTrait
{

    private string $gatewaySchema;
    private string $middlewareSchema;
    private string $middlewareUser;

    public function setConfiguration(string $gatewaySchema, string $middlewareSchema, string $middlewareUser): void
    {
        $this->gatewaySchema = $gatewaySchema;
        $this->middlewareSchema = $middlewareSchema;
        $this->middlewareUser = $middlewareUser;
    }

    public function getGatewaySchema(): string
    {
        if (empty($this->gatewaySchema)) {
            throw new RuntimeException("Gateway schema must be set");
        }
        return $this->gatewaySchema;
    }

    public function getMiddlewareSchema(): string
    {
        if (empty($this->middlewareSchema)) {
            throw new RuntimeException("Middleware schema must be set");
        }
        return $this->middlewareSchema;
    }

    public function getMiddlewareUser(): string
    {
        if (empty($this->middlewareUser)) {
            throw new RuntimeException("Middleware user must be set");
        }
        return $this->middlewareUser;
    }
}

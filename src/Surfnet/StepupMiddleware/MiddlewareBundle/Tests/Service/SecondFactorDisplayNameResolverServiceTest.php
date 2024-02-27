<?php

/**
 * Copyright 2020 SURF bv.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\SecondFactorDisplayNameResolverService;

class SecondFactorDisplayNameResolverServiceTest extends TestCase
{

    /**
     * @test
     */
    public function verify_resolve_displayname(): void
    {
        $factors = ['azuremfa' => 'Azure MFA'];
        $resolver = new SecondFactorDisplayNameResolverService($factors);
        $type = new SecondFactorType('azuremfa');

        self::assertEquals('Azure MFA', $resolver->resolveByType($type));
    }

    /**
     * @test
     */
    public function verify_resolve_displayname_fallback(): void
    {
        $factors = ['azuremfa' => 'Azure MFA'];
        $resolver = new SecondFactorDisplayNameResolverService($factors);
        $type = new SecondFactorType('unknowntoken');

        self::assertEquals('Unknowntoken', $resolver->resolveByType($type));
    }
}

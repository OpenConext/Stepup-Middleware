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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;

final class AuditLogRepositoryTest extends TestCase
{
    #[Test]
    public function the_second_factor_audit_log_query_includes_deprovisioned_entries(): void
    {
        // Regression guard: IdentityForgottenEvent is mapped to the "deprovisioned" audit log
        // action, but entries are only ever returned to RAs if their event FQCN is also present
        // in this allowlist. It has been silently dropped from here once already (during a
        // dedupe-related refactor) despite the projector and backfill command still creating the
        // entries correctly, making the feature a no-op without this test failing.
        $property = new ReflectionProperty(AuditLogRepository::class, 'secondFactorEvents');
        /** @var array<class-string> $secondFactorEvents */
        $secondFactorEvents = $property->getValue();

        $this->assertContains(
            IdentityForgottenEvent::class,
            $secondFactorEvents,
            'IdentityForgottenEvent must stay in the second-factor audit log query allowlist, '
            . 'otherwise deprovisioned entries are silently excluded from the RA audit log API.',
        );
    }
}

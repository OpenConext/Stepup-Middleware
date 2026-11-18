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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Controller;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AuditLogController;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\SecondFactorAuditLogQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\AuditLogService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AuditLogControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockInterface&AuditLogService $auditLogService;

    private MockInterface&AuthorizationContextService $authorizationService;

    private MockInterface&AuthorizationCheckerInterface $authorizationChecker;

    private AuditLogController $controller;

    public function setUp(): void
    {
        $this->auditLogService = Mockery::mock(AuditLogService::class);
        $this->authorizationService = Mockery::mock(AuthorizationContextService::class);
        $this->authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);

        $this->controller = new AuditLogController($this->auditLogService, $this->authorizationService);

        $container = new Container();
        $container->set('security.authorization_checker', $this->authorizationChecker);
        $this->controller->setContainer($container);
    }

    #[Test]
    public function access_is_denied_without_ra_or_read_role(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->andReturn(false);
        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')->never();

        $this->expectException(AccessDeniedException::class);

        $this->controller->secondFactorAuditLog(
            new Request(['identityId' => 'identity-id']),
            new Institution('institution-a.nl'),
        );
    }

    #[Test]
    public function audit_log_is_returned_without_actor_id_for_backwards_compatibility(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_RA', null)->andReturn(true);
        $this->authorizationService->shouldReceive('buildInstitutionAuthorizationContext')->never();

        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')
            ->once()
            ->with(Mockery::on(static fn(SecondFactorAuditLogQuery $query): bool => $query->authorizationContext === null
                && (string) $query->identityId === 'identity-id'
                && (string) $query->identityInstitution === 'institution-a.nl'))
            ->andReturn(new Pagerfanta(new ArrayAdapter([])));

        $response = $this->controller->secondFactorAuditLog(
            new Request(['identityId' => 'identity-id']),
            new Institution('institution-a.nl'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function audit_log_is_returned_for_read_role_without_actor_id(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_RA', null)->andReturn(false);
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_READ', null)->andReturn(true);
        $this->authorizationService->shouldReceive('buildInstitutionAuthorizationContext')->never();
        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')
            ->once()
            ->andReturn(new Pagerfanta(new ArrayAdapter([])));

        $response = $this->controller->secondFactorAuditLog(
            new Request(['identityId' => 'identity-id']),
            new Institution('institution-a.nl'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function actor_id_builds_authorization_context_restricting_the_query(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_RA', null)->andReturn(true);

        $context = new InstitutionAuthorizationContext();
        $this->authorizationService->shouldReceive('buildInstitutionAuthorizationContext')
            ->once()
            ->with(
                Mockery::on(static fn(IdentityId $actorId): bool => (string) $actorId === 'actor-id'),
                Mockery::on(static fn(RegistrationAuthorityRole $role): bool => $role->equals(RegistrationAuthorityRole::ra())),
            )
            ->andReturn($context);

        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')
            ->once()
            ->with(Mockery::on(static fn(SecondFactorAuditLogQuery $query): bool => $query->authorizationContext === $context))
            ->andReturn(new Pagerfanta(new ArrayAdapter([])));

        $response = $this->controller->secondFactorAuditLog(
            new Request(['identityId' => 'identity-id', 'actorId' => 'actor-id']),
            new Institution('institution-a.nl'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function empty_actor_id_is_rejected(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_RA', null)->andReturn(true);
        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')->never();

        $this->expectException(BadApiRequestException::class);

        $this->controller->secondFactorAuditLog(
            new Request(['identityId' => 'identity-id', 'actorId' => '']),
            new Institution('institution-a.nl'),
        );
    }

    #[Test]
    public function missing_identity_id_is_rejected(): void
    {
        $this->authorizationChecker->shouldReceive('isGranted')->with('ROLE_RA', null)->andReturn(true);
        $this->auditLogService->shouldReceive('searchSecondFactorAuditLog')->never();

        $this->expectException(BadApiRequestException::class);

        $this->controller->secondFactorAuditLog(
            new Request([]),
            new Institution('institution-a.nl'),
        );
    }
}

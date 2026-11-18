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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\SecondFactorAuditLogQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;

final class AuditLogRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    #[Test]
    public function the_search_query_is_not_authorization_filtered_without_context(): void
    {
        $filter = Mockery::mock(InstitutionAuthorizationRepositoryFilter::class);
        $filter->shouldNotReceive('filter');

        $repository = $this->createRepository($filter);

        $query = new SecondFactorAuditLogQuery();
        $query->identityInstitution = new Institution('institution-a.nl');
        $query->identityId = new IdentityId('identity-id');

        $this->assertInstanceOf(Query::class, $repository->createSecondFactorSearchQuery($query));
    }

    #[Test]
    public function the_search_query_is_filtered_on_the_actor_institutions_when_context_is_set(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $filter = Mockery::mock(InstitutionAuthorizationRepositoryFilter::class);

        $context = new InstitutionAuthorizationContext();
        $filter->shouldReceive('filter')
            ->once()
            ->with($queryBuilder, $context, 'al.identityInstitution', 'iac');

        $repository = $this->createRepository($filter, $queryBuilder);

        $query = new SecondFactorAuditLogQuery();
        $query->identityInstitution = new Institution('institution-a.nl');
        $query->identityId = new IdentityId('identity-id');
        $query->authorizationContext = $context;

        $this->assertInstanceOf(Query::class, $repository->createSecondFactorSearchQuery($query));
    }

    /**
     * @param MockInterface&InstitutionAuthorizationRepositoryFilter $filter
     */
    private function createRepository(
        InstitutionAuthorizationRepositoryFilter $filter,
        ?MockInterface $queryBuilder = null,
    ): AuditLogRepository {
        $queryBuilder ??= $this->createQueryBuilderMock();

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('createQueryBuilder')->andReturn($queryBuilder);
        $entityManager->shouldReceive('getClassMetadata')
            ->with(AuditLogEntry::class)
            ->andReturn(new ClassMetadata(AuditLogEntry::class));

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManagerForClass')
            ->with(AuditLogEntry::class)
            ->andReturn($entityManager);

        return new AuditLogRepository($registry, $filter);
    }

    /**
     * @return MockInterface&QueryBuilder
     */
    private function createQueryBuilderMock(): QueryBuilder
    {
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('from')->andReturnSelf();
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('andWhere')->andReturnSelf();
        $queryBuilder->shouldReceive('setParameter')->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->andReturnSelf();
        $queryBuilder->shouldReceive('getQuery')->andReturn(Mockery::mock(Query::class));

        return $queryBuilder;
    }
}

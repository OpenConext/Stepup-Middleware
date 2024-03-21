<?php
/**
 * Copyright 2018 SURFnet B.V.
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


namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Authorization\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\Institution as InstitutionValue;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;

class InstitutionAuthorizationRepositoryFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private QueryBuilder $queryBuilder;

    private EntityManager&MockObject $entityManager;

    private InstitutionAuthorizationContextInterface&MockObject $mockedAuthorizationContext;

    public function setUp(): void
    {
        $this->mockedAuthorizationContext = $this->createMock(InstitutionAuthorizationContextInterface::class);

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = new QueryBuilder($this->entityManager);
        $this->queryBuilder->from(InstitutionValue::class, 'i');
    }

    /**
     * @test
     * @group domain
     */
    public function a_querybuilder_object_is_filtered_with_an_institution_authorization_context(): void
    {
        $this->mockedAuthorizationContext->method('getInstitutions')
            ->willReturn(
                new InstitutionCollection([
                    new InstitutionValue('institution-a'),
                    new InstitutionValue('institution-c'),
                ]),
            );

        $authorizationRepositoryFilter = new InstitutionAuthorizationRepositoryFilter();
        $authorizationRepositoryFilter->filter(
            $this->queryBuilder,
            $this->mockedAuthorizationContext,
            'i.institution',
            'iacalias',
        );

        $this->assertEquals(
            sprintf('SELECT FROM %s i WHERE i.institution IN (:iacalias_institutions)', InstitutionValue::class),
            $this->queryBuilder->getDQL(),
        );
        $this->assertEquals(1, $this->queryBuilder->getParameters()->count());
        $this->assertEquals(
            ['institution-a', 'institution-c'],
            $this->queryBuilder->getParameter('iacalias_institutions')->getValue()
        );
    }
}

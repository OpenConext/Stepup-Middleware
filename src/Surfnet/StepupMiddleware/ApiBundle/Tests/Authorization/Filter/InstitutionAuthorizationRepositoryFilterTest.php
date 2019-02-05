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
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Value\Institution as InstitutionValue;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;

class InstitutionAuthorizationRepositoryFilterTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var InstitutionAuthorizationContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockedAuthorizationContext;

    public function setUp()
    {
        $this->mockedAuthorizationContext = $this->createMock(InstitutionAuthorizationContextInterface::class);

        $this->entityManager  = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = new QueryBuilder($this->entityManager);
        $this->queryBuilder->from('institution', 'i');
    }

    /**
     * @test
     * @group domain
     */
    public function a_querybuilder_object_is_filtered_with_an_institution_authorization_context()
    {
        $roleSet = new InstitutionRoleSet([
            InstitutionRole::useRa(),
            InstitutionRole::useRaa(),
        ]);

        $this->mockedAuthorizationContext->method('getRoleRequirements')
            ->willReturn($roleSet);

        $this->mockedAuthorizationContext->method('getIdentityId')
            ->willReturn(new Institution('aaaaaa-1111-1111'));

        $authorizationRepositoryFilter = new InstitutionAuthorizationRepositoryFilter();
        $authorizationRepositoryFilter->filter($this->queryBuilder, $this->mockedAuthorizationContext, 'i.id', 'i.institution', 'iacalias');

        $this->assertEquals('SELECT FROM institution i INNER JOIN Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\InstitutionListing iacalias_institution WITH (iacalias_institution.institution = i.institution) INNER JOIN Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing iacalias_listing WITH (iacalias_listing.raInstitution = iacalias_institution.institution) LEFT JOIN Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization iacalias WITH (iacalias_institution.institution = iacalias.institution AND iacalias.institutionRole = \'use_ra\' OR iacalias.institutionRole = \'use_raa\') WHERE 
            (iacalias_listing.identityId = :iacalias_listing_identityId AND (iacalias_listing.role = \'ra\' OR iacalias_listing.role = \'raa\'))
             GROUP BY i.id', $this->queryBuilder->getDQL());
        $this->assertEquals(1, $this->queryBuilder->getParameters()->count());
        $this->assertEquals('aaaaaa-1111-1111', $this->queryBuilder->getParameter('iacalias_listing_identityId')->getValue());
    }


    /**
     * @test
     * @group domain
     */
    public function a_querybuilder_object_is_filtered_with_an_institution_authorization_context_for_candidates()
    {
        $institution = new InstitutionValue('institution.example.com');

        $authorizationRepositoryFilter = new InstitutionAuthorizationRepositoryFilter();
        $authorizationRepositoryFilter->filterCandidate($this->queryBuilder, $institution, 'i.id', 'i.institution', 'iacalias');

        $this->assertEquals('SELECT FROM institution i INNER JOIN Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization iacalias WITH (iacalias.institutionRelation = i.institution AND (iacalias.institutionRole = \'select_raa\')) WHERE iacalias.institution = :iacalias_institution GROUP BY i.id', $this->queryBuilder->getDQL());
        $this->assertEquals(1, $this->queryBuilder->getParameters()->count());
        $this->assertEquals('institution.example.com', $this->queryBuilder->getParameter('iacalias_institution')->getValue());
    }


    /**
     * @test
     * @group domain
     */
    public function a_querybuilder_object_is_filtered_with_an_institution_authorization_context_for_listing()
    {
        $roleSet = new InstitutionRoleSet([
            InstitutionRole::useRa(),
            InstitutionRole::useRaa(),
        ]);

        $this->mockedAuthorizationContext->method('getRoleRequirements')
            ->willReturn($roleSet);

        $this->mockedAuthorizationContext->method('getActorInstitution')
            ->willReturn(new Institution('institution.example.com'));

        $authorizationRepositoryFilter = new InstitutionAuthorizationRepositoryFilter();
        $authorizationRepositoryFilter->filterListing($this->queryBuilder, $this->mockedAuthorizationContext, 'i.id', 'i.institution', 'iacalias');

        $this->assertEquals('SELECT FROM institution i INNER JOIN Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization iacalias WITH (iacalias.institutionRelation = i.institution AND (iacalias.institutionRole = \'use_ra\' OR iacalias.institutionRole = \'use_raa\')) WHERE iacalias.institution = :iacalias_institution GROUP BY i.id', $this->queryBuilder->getDQL());
        $this->assertEquals(1, $this->queryBuilder->getParameters()->count());
        $this->assertEquals('institution.example.com', $this->queryBuilder->getParameter('iacalias_institution')->getValue());
    }

}
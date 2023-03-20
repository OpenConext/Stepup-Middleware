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

namespace Surfnet\StepupMiddleware\Test\Database;

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuthorizationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test the AuthorizationRepository.
 *
 * This repo is responsible for determining great portions of the FGA authorizations.
 * Having that repository code (DQL) under test will greatly decrease the chance of
 * regressions in that area.
 *
 * Tests in this file are based of a database dump from test2, a quite representative
 * data set was captured there with many FGA scenarios covered.
 */
class AuthorizationRepositoryMatrixTest extends KernelTestCase
{
    /**
     * @var AuthorizationRepository
     */
    private $authzRepository;

    public function authorizationMatrix()
    {
        // Institution roles are used to filter the queries in the institution_authorization projection
        $useRa = InstitutionRole::useRa();
        $useRaa = InstitutionRole::useRaa();
        // The uuids match those of the `Fixtures/test2.sql` data
        $aRa = new IdentityId('eff4d3bc-bbe9-45d4-b80d-080bc7e06615'); // pieter-a-ra
        $aRaa = new IdentityId('947da709-185b-4d9a-ba49-0a22d99dceb3'); // michiel-a-raa (only raa in institution-a)
        $avRaa = new IdentityId('cccfece4-e5e5-40b7-9aa4-a800d7cd3633'); // pieter-a-raa (raa in inst-a and inst-v)
        return [
            'RA from inst-a should have RA rights in inst-a+f' => [$useRa, $aRa, ['institution-a.nl', 'institution-f.nl']],
            'RA from inst-a should not have RAA rights in inst-a+f' => [$useRaa, $aRa, []],
            'RAA from inst-a should have RA rights in inst-a+f' => [$useRa, $aRaa, ['institution-a.nl', 'institution-f.nl']],
            'RAA from inst-a should have RAA rights in inst-a+f' => [$useRaa, $aRaa, ['institution-a.nl', 'institution-f.nl']],
            'RAA from inst-a+v should have RA rights in inst-a+f+i' => [$useRa, $avRaa, ['institution-a.nl', 'institution-f.nl', 'institution-i.nl']],
            'RAA from inst-a+v should have RAA rights in inst-a+f+i' => [$useRaa, $avRaa, ['institution-a.nl', 'institution-f.nl', 'institution-i.nl']],
        ];
    }

    public function selectRaaMatrix()
    {
        $aRaa = new IdentityId('cccfece4-e5e5-40b7-9aa4-a800d7cd3633'); // Raa @ institution A
        $ghRaa = new IdentityId('02b70719-243f-4c7d-8649-48952a816ddf'); // RAA @ institution H

        return [
            'RAA inst-a => select_raa @ inst-a' => [$aRaa, ['institution-a.nl']],
            'RAA inst-h => select_raa @ inst-h+g' => [$ghRaa, ['institution-g.nl', 'institution-h.nl']],
        ];
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->authzRepository = $kernel->getContainer()->get(AuthorizationRepository::class);
        $fixture = file_get_contents(__DIR__ . '/Fixture/test2.sql');
        $manager->getConnection()->exec($fixture);
        $manager->flush();
    }

    /**
     * A test matrix to verify the correct institutions are selected for a given identity for a
     * specific institution role.
     * @dataProvider authorizationMatrix
     */
    public function test_get_institutions_for_role_matrix(
        InstitutionRole $institutionRole,
        IdentityId $identity,
        array $expectedInstitutions
    ) {
        $institutions = $this->authzRepository->getInstitutionsForRole($institutionRole, $identity);
        $results = $this->flattenInstitutionResults($institutions);

        $this->assertEquals(
            $results,
            $expectedInstitutions,
            sprintf(
                'The results do not match the expected results. Actual "%s" versus expected: "%s"',
                implode($results, ','),
                implode($expectedInstitutions, ',')
            )
        );
    }

    /**
     * @dataProvider selectRaaMatrix
     */
    public function test_select_raa_authorization(IdentityId $identityId, array $expected)
    {
        $institutions = $this->authzRepository->getInstitutionsForSelectRaaRole($identityId);
        $this->assertEquals($expected, $this->flattenInstitutionResults($institutions));

    }

    private function flattenInstitutionResults(InstitutionCollection $collection)
    {
        $institutions = [];
        /** @var Institution $institution */
        foreach($collection->jsonSerialize()['institutions'] as $institution)
        {
            $institutions[] = $institution->getInstitution();
        }
        return $institutions;
    }
}

<?php

/**
 * Copyright 2019 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorizedInstitutionCollection;

final class AuthorizedInstitutionCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function empty_collection()
    {
        $collection = AuthorizedInstitutionCollection::fromInstitutionAuthorization([]);
        $this->assertEmpty($collection->getAuthorizations());
    }

    /**
     * @test
     */
    public function retrieve_institutions()
    {
        $homeInstitution = $this->buildInstitution('institution');

        $auth1 = $this->buildAuthorization((string)$homeInstitution, 'institution-x', AuthorityRole::ROLE_RA);
        $auth2 = $this->buildAuthorization((string)$homeInstitution, 'institution-x', AuthorityRole::ROLE_RAA);
        $auth3 = $this->buildAuthorization((string)$homeInstitution, 'institution-y', AuthorityRole::ROLE_RA);
        $collection = AuthorizedInstitutionCollection::fromInstitutionAuthorization(
            [$auth1, $auth2, $auth3]
        );

        $this->assertCount(2, $collection->getAuthorizations());
    }

    private function buildAuthorization($institutionName, $relationName, $role)
    {
        return RaListing::create(
            'identityId',
            new Institution($institutionName),
            new CommonName('commonName'),
            new Email('email@example.com'),
            new AuthorityRole($role),
            new Location('location'),
            new ContactInformation('contactinfo'),
            new Institution($relationName)
        );
    }

    private function buildInstitution($name)
    {
        return new Institution($name);
    }
}

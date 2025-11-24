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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorizedInstitutionCollection;

final class AuthorizedInstitutionCollectionTest extends TestCase
{
    #[Test]
    public function empty_collection(): void
    {
        $collection = AuthorizedInstitutionCollection::from($this->buildInstitutionCollection([]), null);
        $this->assertEmpty($collection->getAuthorizations());
    }

    #[Test]
    public function retrieve_institutions(): void
    {
        $collection = AuthorizedInstitutionCollection::from(
            $this->buildInstitutionCollection(['a', 'b']),
            $this->buildInstitutionCollection(['a', 'b']),
        );

        $this->assertCount(2, $collection->getAuthorizations());

        // Raa roles took precedence over the ra roles.
        $this->assertEquals('raa', $collection->getAuthorizations()['a'][0]);
        $this->assertEquals('raa', $collection->getAuthorizations()['b'][0]);
    }

    #[Test]
    public function retrieve_institutions_only_raa(): void
    {
        $collection = AuthorizedInstitutionCollection::from(
            $this->buildInstitutionCollection([]),
            $this->buildInstitutionCollection(['a', 'b']),
        );

        $this->assertCount(2, $collection->getAuthorizations());

        // Raa roles took precedence over the ra roles.
        $this->assertEquals('raa', $collection->getAuthorizations()['a'][0]);
        $this->assertEquals('raa', $collection->getAuthorizations()['b'][0]);
    }

    /**
     * @param string[] $institutions
     */
    private function buildInstitutionCollection(array $institutions): InstitutionCollection
    {
        $institutionList = [];
        foreach ($institutions as $institution) {
            $institutionList[] = $this->buildInstitution($institution);
        }
        return new InstitutionCollection($institutionList);
    }

    private function buildInstitution(string $name): Institution
    {
        return new Institution($name);
    }
}

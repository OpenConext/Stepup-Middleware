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

namespace Surfnet\Stepup\Tests\Identity\Collection;

use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Exception\RuntimeException;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\Institution;

class InstitutionCollectionTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function it_can_be_constructed_with_or_without_institutions(): void
    {
        $collection1 = new InstitutionCollection($this->getInstitutions());
        $collection2 = new InstitutionCollection();

        $this->assertInstanceOf(InstitutionCollection::class, $collection1);
        $this->assertInstanceOf(InstitutionCollection::class, $collection2);
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function it_correctly_asserts_whether_or_not_it_contains_an_institution(): void
    {
        $institutions = $this->getInstitutions();

        $institutionCollection = new InstitutionCollection($institutions);

        foreach ($institutions as $institution) {
            $this->assertTrue($institutionCollection->contains($institution));
        }

        $this->assertFalse($institutionCollection->contains(new Institution('not listed')));
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function it_allows_to_add_an_institution_that_it_does_not_already_contain(): void
    {
        $toAdd = new Institution('to be added');

        $institutionCollection = new InstitutionCollection();
        $institutionCollection->add($toAdd);

        $this->assertTrue($institutionCollection->contains($toAdd));
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function an_institution_already_in_the_collection_cannot_be_added(): void
    {
        $this->expectException(RuntimeException::class);
        $institutions = $this->getInstitutions();
        $alreadyExists = $institutions[0];

        $institutionCollection = new InstitutionCollection($institutions);

        $this->assertTrue($institutionCollection->contains($alreadyExists));
        $institutionCollection->add($alreadyExists);
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function an_institution_in_the_collection_can_be_removed(): void
    {
        $institutions = $this->getInstitutions();
        $inCollection = $institutions[0];

        $institutionCollection = new InstitutionCollection($institutions);

        $institutionCollection->remove($inCollection);

        $this->assertFalse($institutionCollection->contains($inCollection));
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     *
     */
    public function an_institution_not_in_the_collection_cannot_be_removed(): void
    {
        $this->expectException(RuntimeException::class);

        $institutions = $this->getInstitutions();
        $notInCollection = new Institution('not in the collection');

        $institutionCollection = new InstitutionCollection($institutions);

        $this->assertFalse($institutionCollection->contains($notInCollection));
        $institutionCollection->remove($notInCollection);
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function multiple_institutions_can_be_added_from_another_collection(): void
    {
        $institutions = $this->getInstitutions();
        $collectionOneElements = [$institutions[0], $institutions[1]];
        $collectionTwoElements = [$institutions[2], $institutions[3]];

        $collectionOne = new InstitutionCollection($collectionOneElements);
        $collectionTwo = new InstitutionCollection($collectionTwoElements);

        $collectionOne->addAllFrom($collectionTwo);

        foreach ($collectionTwoElements as $institution) {
            $this->assertTrue($collectionOne->contains($institution));
        }
    }

    /**
     * @test
     * @group        domain
     * @group        whitelist
     */
    public function multiple_institutions_can_be_removed(): void
    {
        $collectionOneElements = $this->getInstitutions();
        $collectionTwoElements = [$collectionOneElements[0], $collectionOneElements[2]];

        $collectionOne = new InstitutionCollection($collectionOneElements);
        $collectionTwo = new InstitutionCollection($collectionTwoElements);

        $collectionOne->removeAllIn($collectionTwo);

        foreach ($collectionTwoElements as $institution) {
            $this->assertFalse($collectionOne->contains($institution));
        }
    }

    /**
     * @return array
     */
    private function getInstitutions()
    {
        static $institutions;

        if ($institutions === null) {
            $institutions = [
                new Institution('Babelfish Inc.'),
                new Institution('The Blue Note'),
                new Institution('SURFnet'),
                new Institution('Ibuildings'),
            ];
        }

        return $institutions;
    }
}

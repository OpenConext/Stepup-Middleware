<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\Entity;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;


class ConfiguredInstitutionTest extends TestCase
{
    /**
     * @test
     * @group entity
     */
    public function serialized_configured_institution_has_the_correct_keys()
    {
        $configuredInstitution = ConfiguredInstitution::createFrom(new Institution('An institution'));

        $serialized   = json_encode($configuredInstitution);
        $deserialized = json_decode($serialized, true);

        $expectedKeys = ['institution'];

        $this->assertCount(
            count($expectedKeys),
            $deserialized,
            'Serialized ConfiguredInstitution does not have the expected amount of keys'
        );

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $deserialized, sprintf('Serialized ConfiguredInstitution is missing key "%s"', $key));
        }
    }

    /**
     * @test
     * @group entity
     */
    public function serialized_configured_institution_has_the_correct_values()
    {
        $configuredInstitution = ConfiguredInstitution::createFrom(new Institution('An institution'));

        $serialized   = json_encode($configuredInstitution);
        $deserialized = json_decode($serialized, true);

        $this->assertSame($configuredInstitution->institution->getInstitution(), $deserialized['institution']);
    }
}

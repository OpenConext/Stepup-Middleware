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

namespace Surfnet\StepupMiddleware\ManagementBundle\Validator;

use Assert\Assertion as Assert;

class RaaConfigurationValidator implements ConfigurationValidatorInterface
{
    public function validate(array $configuration, $propertyPath)
    {
        foreach ($configuration as $entityId => $raaCollection) {
            $path = $propertyPath . '[' . $entityId . ']';
            Assert::string($entityId, 'raa configuration must have entityIds as strings as property', $path);
            Assert::isArray($raaCollection, 'each entityId must have an array of raa configurations as value', $path);

            foreach ($raaCollection as $index => $raaConfiguration) {
                $subPath = $path . '[' . $index . ']';
                $this->validateRaaConfiguration($raaConfiguration, $subPath);
            }
        }
    }

    public function validateRaaConfiguration($raaConfiguration, $subPath)
    {
        Assert::isArray(
            $raaConfiguration,
            "each raa configuration must be an object with properties 'name_id', 'location' and 'contact_info' as value",
            $subPath
        );

        Assert::keyExists($raaConfiguration, 'name_id', 'required property name_id is missing', $subPath);
        Assert::keyExists($raaConfiguration, 'location', 'required property location is missing', $subPath);
        Assert::keyExists($raaConfiguration, 'contact_info', 'required property contact_info is missing', $subPath);

        Assert::string($raaConfiguration['name_id'], 'value must be a string', $subPath . '.name_id');
        Assert::string($raaConfiguration['location'], 'value must be a string', $subPath . '.location');
        Assert::string($raaConfiguration['contact_info'], 'value must be a string', $subPath . '.contact_info');
    }
}

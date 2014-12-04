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
use Assert\InvalidArgumentException as AssertionException;
use GuzzleHttp;
use InvalidArgumentException as CoreInvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Once the Assert 2.0 library has been built this should be converted to the lazy assertions so we can report
 * all errors at once.
 */
class ConfigurationStructureValidator extends ConstraintValidator
{
    /**
     * @var GatewayConfigurationValidator
     */
    private $gatewayConfigurationValidator;

    /**
     * @var RaaConfigurationValidator
     */
    private $raaConfigurationValidator;

    public function __construct(
        GatewayConfigurationValidator $gatewayConfigurationValidator,
        RaaConfigurationValidator $raaConfigurationValidator
    ) {
        $this->gatewayConfigurationValidator = $gatewayConfigurationValidator;
        $this->raaConfigurationValidator = $raaConfigurationValidator;
    }

    public function validate($value, Constraint $constraint)
    {
        /** @var \Symfony\Component\Validator\Violation\ConstraintViolationBuilder|false $violation */
        $violation = false;

        try {
            $decoded = $this->decodeJson($value);
            $this->validateRoot($decoded);
        } catch (AssertionException $exception) {
            // method is not in the interface yet, but the old method is deprecated.
            $violation = $this->context->buildViolation($exception->getMessage());
        } catch (CoreInvalidArgumentException $exception) {
            $violation = $this->context->buildViolation($exception->getMessage());
        }

        if ($violation) {
            $violation->atPath('configuration')->addViolation();
        }
    }

    private function decodeJson($rawValue)
    {
        return GuzzleHttp\json_decode($rawValue, true);
    }

    public function validateRoot(array $configuration)
    {
        Assert::isArray($configuration, 'Invalid body structure, must be an object', '(root)');
        Assert::keyExists($configuration, 'gateway', "Required property 'gateway' is missing", '(root)');
        Assert::keyExists($configuration, 'raa', "Required property 'raa' is missing", '(root)');
        $this->validateGatewayConfiguration($configuration, 'gateway');
        $this->validateRaaConfiguration($configuration, 'raa');
    }

    private function validateGatewayConfiguration($configuration, $propertyPath)
    {
        Assert::isArray($configuration['gateway'], 'Property "gateway" must have an object as value', $propertyPath);

        $this->gatewayConfigurationValidator->validate($configuration['gateway'], $propertyPath);
    }

    private function validateRaaConfiguration($configuration, $propertyPath)
    {
        Assert::isArray($configuration['raa'], 'Property "raa" must have an object as value', $propertyPath);

        $this->raaConfigurationValidator->validate($configuration['raa'], $propertyPath);
    }
}

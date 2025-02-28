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

use Assert\Assertion;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException as AssertionException;
use InvalidArgumentException as CoreInvalidArgumentException;
use Surfnet\Stepup\Helper\JsonHelper;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use TypeError;

/**
 * Once the Assert 2.0 library has been built this should be converted to the lazy assertions so we can report
 * all errors at once.
 */
class ConfigurationStructureValidator extends ConstraintValidator
{
    public function __construct(
        private readonly GatewayConfigurationValidator $gatewayConfigurationValidator,
        private readonly EmailTemplatesConfigurationValidator $emailTemplatesConfigurationValidator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var ConstraintViolationBuilder|false $violation */
        $violation = false;

        try {
            $decoded = $this->decodeJson($value);
            $this->validateRoot($decoded);
        } catch (AssertionException $exception) {
            // method is not in the interface yet, but the old method is deprecated.
            $violation = $this->context->buildViolation($exception->getMessage());
            $violation->atPath($exception->getPropertyPath());
        } catch (CoreInvalidArgumentException|TypeError $exception) {
            $violation = $this->context->buildViolation($exception->getMessage());
        }

        if ($violation) {
            // ensure we have a sensible path.
            $violation->addViolation();
        }
    }

    private function decodeJson(string $rawValue): mixed
    {
        return JsonHelper::decode($rawValue);
    }

    public function validateRoot(array $configuration): void
    {
        $acceptedProperties = ['gateway', 'sraa', 'email_templates'];
        StepupAssert::keysMatch(
            $configuration,
            $acceptedProperties,
            sprintf("Expected only properties '%s'", implode(',', $acceptedProperties)),
            '(root)',
        );

        $this->validateGatewayConfiguration($configuration, 'gateway');
        $this->validateSraaConfiguration($configuration, 'sraa');
        $this->validateEmailTemplatesConfiguration($configuration, 'email_templates');
    }

    private function validateGatewayConfiguration(array $configuration, string $propertyPath): void
    {
        Assertion::isArray($configuration['gateway'], 'Property "gateway" must have an object as value', $propertyPath);

        $this->gatewayConfigurationValidator->validate($configuration['gateway'], $propertyPath);
    }

    private function validateSraaConfiguration(array $configuration, string $propertyPath): void
    {
        Assertion::isArray(
            $configuration['sraa'],
            'Property sraa must have an array of name_ids (string) as value',
            $propertyPath,
        );

        foreach ($configuration['sraa'] as $index => $value) {
            Assertion::string(
                $value,
                'value must be a string (the name_id of the SRAA)',
                $propertyPath . '[' . $index . ']',
            );
        }
    }

    /**
     * @param array<string, mixed> $configuration
     * @throws AssertionFailedException
     */
    private function validateEmailTemplatesConfiguration(array $configuration, string $propertyPath): void
    {
        Assertion::isArray(
            $configuration['email_templates'],
            'Property "email_templates" must have an object as value',
            $propertyPath,
        );

        $this->emailTemplatesConfigurationValidator->validate($configuration['email_templates'], $propertyPath);
    }
}

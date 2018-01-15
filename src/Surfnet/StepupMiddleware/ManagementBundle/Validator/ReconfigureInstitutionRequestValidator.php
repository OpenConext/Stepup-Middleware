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

namespace Surfnet\StepupMiddleware\ManagementBundle\Validator;

use Assert\Assertion;
use Assert\InvalidArgumentException as AssertionException;
use InvalidArgumentException as CoreInvalidArgumentException;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\ConfiguredInstitutionService;
use Surfnet\StepupMiddleware\ManagementBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ReconfigureInstitutionRequestValidator extends ConstraintValidator
{
    /**
     * @var ConfiguredInstitutionService
     */
    private $configuredInstitutionsService;

    /**
     * @var string[] internal cache, access through getConfiguredInstitutions()
     */
    private $configuredInstitutions;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;

    public function __construct(
        ConfiguredInstitutionService $configuredInstitutionsService,
        SecondFactorTypeService $secondFactorTypeService
    ) {
        $this->configuredInstitutionsService = $configuredInstitutionsService;
        $this->secondFactorTypeService = $secondFactorTypeService;
    }

    public function validate($value, Constraint $constraint)
    {
        /** @var \Symfony\Component\Validator\Violation\ConstraintViolationBuilder|false $violation */
        $violation = false;

        try {
            $this->validateRoot($value);
        } catch (AssertionException $exception) {
            // method is not in the interface yet, but the old method is deprecated.
            $violation = $this->context->buildViolation($exception->getMessage());
            $violation->atPath($exception->getPropertyPath());
        } catch (CoreInvalidArgumentException $exception) {
            $violation = $this->context->buildViolation($exception->getMessage());
        }

        if ($violation) {
            $violation->addViolation();
        }
    }

    public function validateRoot(array $configuration)
    {
        Assertion::isArray($configuration, 'Invalid body structure, must be an object', '(root)');
        $this->validateInstitutionsExist(array_keys($configuration));

        foreach ($configuration as $institution => $options) {
            $this->validateInstitutionConfigurationOptions($options, $institution);
        }
    }

    /**
     * @param array $institutions
     */
    public function validateInstitutionsExist(array $institutions)
    {
        $configuredInstitutions = $this->getConfiguredInstitutions();

        $nonExistentInstitutions = $this->determineNonExistentInstitutions($institutions, $configuredInstitutions);

        if (!empty($nonExistentInstitutions)) {
            throw new InvalidArgumentException(
                sprintf('Cannot reconfigure non-existent institution(s): %s', implode(', ', $nonExistentInstitutions))
            );
        }
    }

    /**
     * @param array $options
     * @param string $institution
     */
    public function validateInstitutionConfigurationOptions($options, $institution)
    {
        $propertyPath = sprintf('Institution(%s)', $institution);

        Assertion::isArray($options, 'Invalid institution configuration, must be an object', $propertyPath);

        $acceptedOptions = ['use_ra_locations', 'show_raa_contact_information', 'verify_email', 'allowed_second_factors'];
        StepupAssert::keysMatch(
            $options,
            $acceptedOptions,
            sprintf('Expected only options "%s" for "%s"', join(', ', $acceptedOptions), $institution),
            $propertyPath
        );

        Assertion::boolean(
            $options['use_ra_locations'],
            sprintf('Option "use_ra_locations" for "%s" must be a boolean value', $institution),
            $propertyPath
        );

        Assertion::boolean(
            $options['show_raa_contact_information'],
            sprintf('Option "show_raa_contact_information" for "%s" must be a boolean value', $institution),
            $propertyPath
        );

        Assertion::boolean(
            $options['verify_email'],
            sprintf('Option "verify_email" for "%s" must be a boolean value', $institution),
            $propertyPath
        );

        Assertion::isArray(
            $options['allowed_second_factors'],
            sprintf('Option "allowed_second_factors" for "%s" must be an array of strings', $institution),
            $propertyPath
        );
        Assertion::allString(
            $options['allowed_second_factors'],
            sprintf('Option "allowed_second_factors" for "%s" must be an array of strings', $institution),
            $propertyPath
        );
        Assertion::allInArray(
            $options['allowed_second_factors'],
            $this->secondFactorTypeService->getAvailableSecondFactorTypes(),
            'Option "allowed_second_factors" for "%s" must contain valid second factor types',
            $propertyPath
        );
    }

    /**
     * Accessor for configured institutions to be able to use an internal cache
     *
     * @return string[]
     */
    private function getConfiguredInstitutions()
    {
        if (!empty($this->configuredInstitutions)) {
            return $this->configuredInstitutions;
        }

        $this->configuredInstitutions = array_map(
            function (ConfiguredInstitution $configuredInstitution) {
                return $configuredInstitution->institution->getInstitution();
            },
            $this->configuredInstitutionsService->getAll()
        );

        return $this->configuredInstitutions;
    }

    /**
     * @param string[] $institutions
     * @param $configuredInstitutions
     * @return string[]
     */
    public function determineNonExistentInstitutions(array $institutions, $configuredInstitutions)
    {
        $normalizedConfiguredInstitutions = array_map(
            function ($institution) {
                return strtolower($institution);
            },
            $configuredInstitutions
        );

        return array_filter(
            $institutions,
            function ($institution) use ($normalizedConfiguredInstitutions) {
                $normalizedInstitution = strtolower($institution);

                return !in_array($normalizedInstitution, $normalizedConfiguredInstitutions);
            }
        );
    }
}

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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\ConfiguredInstitutionService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\WhitelistEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
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

    /**
     * @var WhitelistService
     */
    private $whitelistService;

    /**
     * @var string[] internal cache, access through getWhitelistedInstitutions()
     */
    private $whitelistedInstitutions;

    public function __construct(
        ConfiguredInstitutionService $configuredInstitutionsService,
        SecondFactorTypeService $secondFactorTypeService,
        WhitelistService $whitelistService
    ) {
        $this->configuredInstitutionsService = $configuredInstitutionsService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->whitelistService = $whitelistService;
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

        $requiredOptions = [
            'use_ra_locations',
            'show_raa_contact_information',
            'verify_email',
            'self_vet',
            'allow_self_asserted_tokens',
            'number_of_tokens_per_identity',
            'allowed_second_factors',
        ];

        $optionalOptions = [
            'use_ra',
            'use_raa',
            'select_raa',
        ];

        StepupAssert::requiredAndOptionalOptions(
            $options,
            $requiredOptions,
            $optionalOptions,
            sprintf('Expected only options "%s" for "%s"', join(', ', $requiredOptions), $institution),
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

        Assertion::boolean(
            $options['self_vet'],
            sprintf('Option "self_vet" for "%s" must be a boolean value', $institution),
            $propertyPath
        );

        Assertion::integer(
            $options['number_of_tokens_per_identity'],
            sprintf('Option "number_of_tokens_per_identity" for "%s" must be an integer value', $institution),
            $propertyPath
        );

        Assertion::min(
            $options['number_of_tokens_per_identity'],
            0,
            sprintf('Option "number_of_tokens_per_identity" for "%s" must be greater than or equal to 0', $institution),
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

        $this->validateAuthorizationSettings($options, $institution, $propertyPath);
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
     * Accessor for whitelisted institutions to be able to use an internal cache
     *
     * @return string[]
     */
    private function getWhitelistedInstitutions()
    {
        if (!empty($this->whitelistedInstitutions)) {
            return $this->whitelistedInstitutions;
        }

        $this->whitelistedInstitutions = array_map(
            function (WhitelistEntry $whitelistEntry) {
                return (string)$whitelistEntry->institution;
            },
            $this->whitelistService->getAllEntries()->toArray()
        );

        return $this->whitelistedInstitutions;
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

    /**
     * Validates if the authorization_settings array is configured correctly
     *
     *  - The optional options should contain whitelisted institutions
     *  - Or be empty
     *
     * @param $authorizationSettings
     * @param $institution
     * @param $propertyPath
     * @throws \Assert\AssertionFailedException
     */
    private function validateAuthorizationSettings($authorizationSettings, $institution, $propertyPath)
    {
        $acceptedOptions = [
            'use_ra',
            'use_raa',
            'select_raa',
        ];

        $whitelistedInstitutions = $this->getWhitelistedInstitutions();

        foreach ($authorizationSettings as $optionName => $setting) {
            if (in_array($optionName, $acceptedOptions)) {
                // 1. Value must be array
                Assertion::isArray(
                    $authorizationSettings[$optionName],
                    sprintf(
                        'Option "%s" for "%s" must be an array of strings. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true)
                    ),
                    $propertyPath
                );

                // 2. The contents of the array must be empty or string
                Assertion::allString(
                    $authorizationSettings[$optionName],
                    sprintf(
                        'All values of option "%s" should be of type string. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true)
                    ),
                    $propertyPath
                );

                // 3. The institutions that are used in the configuration, should be known, configured, institutions
                Assertion::allInArray(
                    $authorizationSettings[$optionName],
                    $whitelistedInstitutions,
                    sprintf(
                        'All values of option "%s" should be known institutions. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true)
                    ),
                    $propertyPath
                );
            }
        }
    }
}

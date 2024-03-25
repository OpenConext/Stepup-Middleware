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
use Assert\AssertionFailedException;
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
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

final class ReconfigureInstitutionRequestValidator extends ConstraintValidator
{
    /**
     * @var string[] internal cache, access through getConfiguredInstitutions()
     */
    private ?array $configuredInstitutions = null;

    /**
     * @var string[] internal cache, access through getWhitelistedInstitutions()
     */
    private ?array $whitelistedInstitutions = null;

    public function __construct(
        private readonly ConfiguredInstitutionService $configuredInstitutionsService,
        private readonly SecondFactorTypeService $secondFactorTypeService,
        private readonly WhitelistService $whitelistService,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var ConstraintViolationBuilder|false $violation */
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

    public function validateRoot(array $configuration): void
    {
        Assertion::isArray($configuration, 'Invalid body structure, must be an object', '(root)');
        $this->validateInstitutionsExist(array_keys($configuration));

        foreach ($configuration as $institution => $options) {
            $this->validateInstitutionConfigurationOptions($options, $institution);
        }
    }

    public function validateInstitutionsExist(array $institutions): void
    {
        $configuredInstitutions = $this->getConfiguredInstitutions();

        $nonExistentInstitutions = $this->determineNonExistentInstitutions($institutions, $configuredInstitutions);

        if ($nonExistentInstitutions !== []) {
            throw new InvalidArgumentException(
                sprintf('Cannot reconfigure non-existent institution(s): %s', implode(', ', $nonExistentInstitutions)),
            );
        }
    }

    public function validateInstitutionConfigurationOptions(array $options, string $institution): void
    {
        $propertyPath = sprintf('Institution(%s)', $institution);
        Assertion::isArray($options, 'Invalid institution configuration, must be an object', $propertyPath);
        $requiredOptions = [
            'use_ra_locations',
            'show_raa_contact_information',
            'verify_email',
            'number_of_tokens_per_identity',
            'allowed_second_factors',
        ];
        $optionalOptions = [
            'self_vet',
            'sso_on_2fa',
            'allow_self_asserted_tokens',
            'use_ra',
            'use_raa',
            'select_raa',
        ];
        StepupAssert::requiredAndOptionalOptions(
            $options,
            $requiredOptions,
            $optionalOptions,
            sprintf(
                'Invalid option(s) for "%s". Required options: "%s"; Optional options: "%s"',
                $institution,
                implode(', ', $requiredOptions),
                implode(', ', $optionalOptions),
            ),
            $propertyPath,
        );
        Assertion::boolean(
            $options['use_ra_locations'],
            sprintf('Option "use_ra_locations" for "%s" must be a boolean value', $institution),
            $propertyPath,
        );
        Assertion::boolean(
            $options['show_raa_contact_information'],
            sprintf('Option "show_raa_contact_information" for "%s" must be a boolean value', $institution),
            $propertyPath,
        );
        Assertion::boolean(
            $options['verify_email'],
            sprintf('Option "verify_email" for "%s" must be a boolean value', $institution),
            $propertyPath,
        );
        if (isset($options['self_vet'])) {
            Assertion::boolean(
                $options['self_vet'],
                sprintf('Option "self_vet" for "%s" must be a boolean value', $institution),
                $propertyPath,
            );
        }
        if (isset($options['sso_on_2fa'])) {
            Assertion::boolean(
                $options['sso_on_2fa'],
                sprintf('Option "sso_on_2fa" for "%s" must be a boolean value', $institution),
                $propertyPath,
            );
        }
        if (isset($options['allow_self_asserted_tokens'])) {
            Assertion::nullOrBoolean(
                $options['allow_self_asserted_tokens'],
                sprintf('Option "allow_self_asserted_tokens" for "%s" must be a boolean value', $institution),
                $propertyPath,
            );
        }
        Assertion::integer(
            $options['number_of_tokens_per_identity'],
            sprintf('Option "number_of_tokens_per_identity" for "%s" must be an integer value', $institution),
            $propertyPath,
        );
        Assertion::min(
            $options['number_of_tokens_per_identity'],
            0,
            sprintf('Option "number_of_tokens_per_identity" for "%s" must be greater than or equal to 0', $institution),
            $propertyPath,
        );
        Assertion::isArray(
            $options['allowed_second_factors'],
            sprintf('Option "allowed_second_factors" for "%s" must be an array of strings', $institution),
            $propertyPath,
        );
        Assertion::allString(
            $options['allowed_second_factors'],
            sprintf('Option "allowed_second_factors" for "%s" must be an array of strings', $institution),
            $propertyPath,
        );
        Assertion::allInArray(
            $options['allowed_second_factors'],
            $this->secondFactorTypeService->getAvailableSecondFactorTypes(),
            'Option "allowed_second_factors" for "%s" must contain valid second factor types',
            $propertyPath,
        );
        $this->validateAuthorizationSettings($options, $institution, $propertyPath);
    }

    /**
     * Accessor for configured institutions to be able to use an internal cache
     *
     * @return string[]
     */
    private function getConfiguredInstitutions(): array
    {
        if ($this->configuredInstitutions !== null && $this->configuredInstitutions !== []) {
            return $this->configuredInstitutions;
        }

        $this->configuredInstitutions = array_map(
            fn(ConfiguredInstitution $configuredInstitution): string => $configuredInstitution->institution->getInstitution(),
            $this->configuredInstitutionsService->getAll(),
        );

        return $this->configuredInstitutions;
    }

    /**
     * Accessor for whitelisted institutions to be able to use an internal cache
     *
     * @return string[]
     */
    private function getWhitelistedInstitutions(): array
    {
        if ($this->whitelistedInstitutions !== null && $this->whitelistedInstitutions !== []) {
            return $this->whitelistedInstitutions;
        }

        $this->whitelistedInstitutions = array_map(
            fn(WhitelistEntry $whitelistEntry): string => (string)$whitelistEntry->institution,
            $this->whitelistService->getAllEntries()->toArray(),
        );

        return $this->whitelistedInstitutions;
    }

    /**
     * @param string[] $institutions
     * @param string[] $configuredInstitutions
     * @return string[]
     */
    public function determineNonExistentInstitutions(array $institutions, array $configuredInstitutions): array
    {
        $normalizedConfiguredInstitutions = array_map(
            fn($institution): string => strtolower((string)$institution),
            $configuredInstitutions,
        );

        return array_filter(
            $institutions,
            function ($institution) use ($normalizedConfiguredInstitutions): bool {
                $normalizedInstitution = strtolower($institution);

                return !in_array($normalizedInstitution, $normalizedConfiguredInstitutions);
            },
        );
    }

    /**
     * Validates if the authorization_settings array is configured correctly
     *
     *  - The optional options should contain whitelisted institutions
     *  - Or be empty
     *
     * @throws AssertionFailedException
     */
    private function validateAuthorizationSettings(
        array $authorizationSettings,
        string $institution,
        string $propertyPath,
    ): void {
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
                    $setting,
                    sprintf(
                        'Option "%s" for "%s" must be an array of strings. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true),
                    ),
                    $propertyPath,
                );

                // 2. The contents of the array must be empty or string
                Assertion::allString(
                    $setting,
                    sprintf(
                        'All values of option "%s" for "%s" should be of type string. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true),
                    ),
                    $propertyPath,
                );

                // 3. The institutions that are used in the configuration, should be known, configured, institutions
                Assertion::allInArray(
                    $authorizationSettings[$optionName],
                    $whitelistedInstitutions,
                    sprintf(
                        'All values of option "%s" for "%s" should be known institutions. ("%s") was passed.',
                        $optionName,
                        $institution,
                        var_export($setting, true),
                    ),
                    $propertyPath,
                );
            }
        }
    }
}

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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class InstitutionConfigurationOptionsController extends AbstractController
{
    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @return InstitutionAuthorizationService
     */
    private $institutionAuthorizationService;

    /**
     * @var AllowedSecondFactorListService
     */
    private $allowedSecondFactorListService;

    public function __construct(
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        InstitutionAuthorizationService $institutionAuthorizationService,
        AllowedSecondFactorListService $allowedSecondFactorListService
    ) {
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->institutionAuthorizationService = $institutionAuthorizationService;
        $this->allowedSecondFactorListService = $allowedSecondFactorListService;
    }

    public function getForInstitutionAction($institutionName)
    {
        $this->denyAccessUnlessGranted(['ROLE_SS', 'ROLE_RA', 'ROLE_READ']);

        $institution = new Institution($institutionName);

        $institutionConfigurationOptions = $this
            ->institutionConfigurationOptionsService
            ->findInstitutionConfigurationOptionsFor($institution);

        $allowedSecondFactorList = $this
            ->allowedSecondFactorListService
            ->getAllowedSecondFactorListFor($institution);

        if ($institutionConfigurationOptions === null) {
            throw new NotFoundHttpException(
                sprintf('No institution configuration options found for institution "%s"', $institution)
            );
        }

        $numberOfTokensPerIdentity = $this
            ->institutionConfigurationOptionsService
            ->getMaxNumberOfTokensFor($institution);

        // Get the authorization options for this institution
        $institutionConfigurationOptionsMap = $this->institutionAuthorizationService
            ->findAuthorizationsFor($institution);

        return new JsonResponse([
            'institution'                  => $institutionConfigurationOptions->institution,
            'use_ra_locations'             => $institutionConfigurationOptions->useRaLocationsOption,
            'show_raa_contact_information' => $institutionConfigurationOptions->showRaaContactInformationOption,
            'verify_email'                 => $institutionConfigurationOptions->verifyEmailOption,
            'sso_on_2fa' => $institutionConfigurationOptions->ssoOn2faOption,
            'self_vet' => $institutionConfigurationOptions->selfVetOption,
            'allow_self_asserted_tokens' => $institutionConfigurationOptions->selfAssertedTokensOption,
            'number_of_tokens_per_identity' => $numberOfTokensPerIdentity,
            'allowed_second_factors'       => $allowedSecondFactorList,
            'use_ra' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::useRa())->jsonSerialize(),
            'use_raa' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::useRaa())->jsonSerialize(),
            'select_raa' => $institutionConfigurationOptionsMap->getAuthorizationOptionsByRole(InstitutionRole::selectRaa())->jsonSerialize(),
        ]);
    }
}

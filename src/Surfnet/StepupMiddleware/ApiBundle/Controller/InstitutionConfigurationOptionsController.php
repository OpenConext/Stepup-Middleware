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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class InstitutionConfigurationOptionsController extends Controller
{
    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    /**
     * @var AllowedSecondFactorListService
     */
    private $allowedSecondFactorListService;

    public function __construct(
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        AllowedSecondFactorListService $allowedSecondFactorListService
    ) {
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->allowedSecondFactorListService = $allowedSecondFactorListService;
    }

    public function getForInstitutionAction($institutionName)
    {
        $this->denyAccessUnlessGranted(['ROLE_SS', 'ROLE_RA']);

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

        return new JsonResponse([
            'institution'                  => $institutionConfigurationOptions->institution,
            'use_ra_locations'             => $institutionConfigurationOptions->useRaLocationsOption,
            'show_raa_contact_information' => $institutionConfigurationOptions->showRaaContactInformationOption,
            'verify_email'                 => $institutionConfigurationOptions->verifyEmailOption,
            'number_of_tokens_per_identity' => $numberOfTokensPerIdentity,
            'allowed_second_factors'       => $allowedSecondFactorList,
        ]);
    }
}

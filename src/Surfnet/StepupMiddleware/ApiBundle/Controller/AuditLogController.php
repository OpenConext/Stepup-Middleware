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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\SecondFactorAuditLogQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\AuditLogService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

final class AuditLogController extends Controller
{
    /**
     * @var AuditLogService
     */
    private $auditLogService;

    public function __construct(AuditLogService $service)
    {
        $this->auditLogService = $service;
    }

    public function secondFactorAuditLogAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $identityId = $request->get('identityId');
        if (empty($identityId)) {
            throw new BadApiRequestException(['This API-call MUST include the identityId as get parameter']);
        }

        $query                      = new SecondFactorAuditLogQuery();
        $query->identityInstitution = $institution;
        $query->identityId          = new IdentityId($identityId);
        $query->orderBy             = $request->get('orderBy', $query->orderBy);
        $query->orderDirection      = $request->get('orderDirection', $query->orderDirection);
        $query->pageNumber          = $request->get('p', 1);

        $paginator = $this->auditLogService->searchSecondFactorAuditLog($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}

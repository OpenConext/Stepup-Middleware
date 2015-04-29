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

use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class RaCandidateController extends Controller
{
    public function searchAction(Institution $institution, Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query              = new RaCandidateQuery();
        $query->institution = $institution;
        $query->commonName  = $request->get('commonName');
        $query->email       = $request->get('email');
        $query->pageNumber  = (int) $request->get('p', 1);

        $paginator = $this->get('surfnet_stepup_middleware_api.service.ra_candidate')->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}

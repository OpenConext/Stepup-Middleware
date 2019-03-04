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

namespace Surfnet\StepupMiddleware\ApiBundle\Response;

use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonCollectionResponse extends JsonResponse
{
    public static function fromPaginator(Pagerfanta $paginator, $filters = array())
    {
        return new self(
            $paginator->getNbResults(),
            $paginator->getCurrentPage(),
            $paginator->getMaxPerPage(),
            (array) $paginator->getCurrentPageResults(),
            array(),
            $filters
        );
    }

    /**
     * @param int $totalItems
     * @param int $page
     * @param int $pageSize
     * @param array $collection
     * @param array $headers
     * @param array $filters
     */
    public function __construct($totalItems, $page, $pageSize, array $collection, $headers = array(), $filters = array())
    {
        $data = array(
            'collection' => array(
                'total_items' => $totalItems,
                'page'        => $page,
                'page_size'   => $pageSize
            ),
            'items'      => $collection
        );

        if (!empty($filters)) {
            $data['filters'] = $filters;
        }

        parent::__construct($data, 200, $headers);
    }
}

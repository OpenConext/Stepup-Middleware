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

namespace Surfnet\StepupMiddleware\ApiBundle\EventListener;

use Broadway\Repository\AggregateNotFoundException;
use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception\InvalidCommandException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Converts all exceptions into JSON responses.
 */
class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $this->logException($exception);

        if ($exception instanceof HttpExceptionInterface && $exception instanceof Exception) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } else {
            $statusCode = $exception instanceof BadApiRequestException
                    || $exception instanceof BadCommandRequestException
                    || $exception instanceof DomainException
                    || $exception instanceof AggregateNotFoundException
                ? 400
                : 500;

            $headers = [];
        }

        $event->setResponse($this->createJsonErrorResponse($exception, $statusCode, $headers));
    }

    private function logException(Exception $exception)
    {
        # As per \Symfony\Component\HttpKernel\EventListener\ExceptionListener#logException().
        $isCritical = !$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500;

        if ($isCritical) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        } else {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }
    }

    /**
     * @param Exception $exception
     * @param int $statusCode
     * @param array $headers OPTIONAL
     * @return JsonResponse
     */
    private function createJsonErrorResponse(Exception $exception, $statusCode, $headers = [])
    {
        if ($exception instanceof BadApiRequestException
            || $exception instanceof BadCommandRequestException
            || $exception instanceof InvalidCommandException
        ) {
            $errors = $exception->getErrors();
        } else {
            $errors = [$exception->getMessage()];
        }

        return new JsonResponse(['errors' => $errors], $statusCode, $headers);
    }
}

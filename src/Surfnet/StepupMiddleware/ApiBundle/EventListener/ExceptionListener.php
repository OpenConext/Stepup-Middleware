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
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception\InvalidCommandException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Converts all exceptions into JSON responses.
 */
class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $this->logException($throwable);

        if ($throwable instanceof HttpExceptionInterface && $throwable instanceof Throwable) {
            $statusCode = $throwable->getStatusCode();
            $headers = $throwable->getHeaders();
        } else {
            $statusCode = $throwable instanceof BadApiRequestException
            || $throwable instanceof BadCommandRequestException
            || $throwable instanceof DomainException
            || $throwable instanceof AggregateNotFoundException
                ? 400
                : 500;

            $headers = [];
        }

        $event->setResponse($this->createJsonErrorResponse($throwable, $statusCode, $headers));
    }

    private function logException(Throwable $throwable): void
    {
        # As per \Symfony\Component\HttpKernel\EventListener\ExceptionListener#logException().
        $isCritical = !$throwable instanceof HttpExceptionInterface || $throwable->getStatusCode() >= 500;

        if ($isCritical) {
            $this->logger->critical($throwable->getMessage(), ['exception' => $throwable]);
        } else {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    /**
     * @param Throwable $exception
     * @param array $headers OPTIONAL
     * @return JsonResponse
     */
    private function createJsonErrorResponse(Throwable $throwable, int $statusCode, array $headers = []): JsonResponse
    {
        if ($throwable instanceof BadApiRequestException
            || $throwable instanceof BadCommandRequestException
            || $throwable instanceof InvalidCommandException
        ) {
            $errors = $throwable->getErrors();
        } else {
            $errors = [sprintf('%s: %s', $throwable::class, $throwable->getMessage())];
        }

        return new JsonResponse(['errors' => $errors], $statusCode, $headers);
    }
}

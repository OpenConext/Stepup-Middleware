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

namespace Surfnet\StepupMiddleware\ApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

/**
 * Thrown when a client provided invalid command input to the application.
 */
class BadCommandRequestException extends RuntimeException
{
    public static function withViolations(string $message, ConstraintViolationListInterface $violations): self
    {
        $violationStrings = self::convertViolationsToStrings($violations);
        $message = sprintf('%s (%s)', $message, implode('; ', $violationStrings));

        return new self($violationStrings, $message);
    }

    /**
     * @return string[]
     */
    private static function convertViolationsToStrings(ConstraintViolationListInterface $violations): array
    {
        $violationStrings = [];

        foreach ($violations as $violation) {
            /** @var ConstraintViolationInterface $violation */
            $violationStrings[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return $violationStrings;
    }

    public function __construct(
        private readonly array $errors,
        string $message = 'JSON could not be reconstituted into valid object.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

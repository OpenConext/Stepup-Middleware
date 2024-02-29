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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception;

use Exception;
use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidCommandException extends RuntimeException implements ProcessingAbortedException
{
    /**
     * @var string[]
     */
    private readonly array $errors;

    public static function createFromViolations(ConstraintViolationListInterface $violations): self
    {
        return new self(self::mapViolationsToErrorStrings($violations));
    }

    public function __construct(array $errors, $code = 0, Exception $previous = null)
    {
        parent::__construct(sprintf('Command is invalid: %s', implode('; ', $errors)), $code, $previous);

        $this->errors = $errors;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    private static function mapViolationsToErrorStrings(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            /** @var ConstraintViolationInterface $violation */
            $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return $errors;
    }
}

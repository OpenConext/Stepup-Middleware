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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData;

use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;

class SensitiveData implements JsonSerializable
{
    const COMMON_NAME = 'common_name';
    const EMAIL = 'email';
    const SECOND_FACTOR_IDENTIFIER = 'second_factor_identifier';
    const DOCUMENT_NUMBER = 'document_number';

    /**
     * @var array|null
     */
    private $data;

    /**
     * @param array|null $data A hash of toString-able value objects, indexed by their data keys listed in the constants
     *    in this class, or null, indicating the sensitive data has been forgotten.
     * @throws InvalidArgumentException If a data key is not one of the SensitiveData class constants
     */
    public function __construct(array $data = null)
    {
        if (is_array($data)) {
            $validKeys = [self::COMMON_NAME, self::EMAIL, self::SECOND_FACTOR_IDENTIFIER, self::DOCUMENT_NUMBER];
            $invalidKeys = array_diff(array_keys($data), $validKeys);
            if (count($invalidKeys) > 0) {
                throw new InvalidArgumentException(
                    sprintf('Sensitive data contains invalid keys "%s"', join('", "', $invalidKeys))
                );
            }

            $this->data = array_map('strval', $data);
        } else {
            $this->data = null;
        }
    }

    /**
     * Returns an instance in which all sensitive data is forgotten.
     *
     * @return SensitiveData
     */
    public function forget()
    {
        return new self(null);
    }

    /**
     * @return CommonName
     */
    public function getCommonName()
    {
        return $this->data
            ? new CommonName($this->data[self::COMMON_NAME])
            : CommonName::unknown();
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->data
            ? new Email($this->data[self::EMAIL])
            : Email::unknown();
    }

    /**
     * @param SecondFactorType $secondFactorType
     * @return SecondFactorIdentifier
     */
    public function getSecondFactorIdentifier(SecondFactorType $secondFactorType)
    {
        return $this->data
            ? SecondFactorIdentifierFactory::forType($secondFactorType, $this->data[self::SECOND_FACTOR_IDENTIFIER])
            : SecondFactorIdentifierFactory::unknownForType($secondFactorType);
    }

    /**
     * @return DocumentNumber
     */
    public function getDocumentNumber()
    {
        return $this->data
            ? new DocumentNumber($this->data[self::DOCUMENT_NUMBER])
            : DocumentNumber::unknown();
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}

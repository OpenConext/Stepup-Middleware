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

namespace Surfnet\Stepup\Identity\Value;

final class VettingTypeFactory
{
    public static function fromData(array $data): VettingType
    {
        $vettingType = new UnknownVettingType();
        if (isset($data['vetting_type'])) {
            switch ($data['vetting_type']['type']) {
                case VettingType::TYPE_SELF_VET:
                    $vettingType = SelfVetVettingType::deserialize($data['vetting_type']);
                    break;
                case VettingType::TYPE_ON_PREMISE:
                    $vettingType = OnPremiseVettingType::deserialize($data['vetting_type']);
                    break;
            }
        }
        // BC fix for older events without a vetting type, they default back to ON_PREMISE.
        if ($vettingType instanceof UnknownVettingType &&
            isset($data['document_number']) &&
            $data['document_number'] !== null
        ) {
            $vettingType = new OnPremiseVettingType(new DocumentNumber($data['document_number']));
        }

        return $vettingType;
    }
}

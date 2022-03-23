<?php

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData;

interface RightToObtainDataInterface
{
    public function obtainUserData(): array;
}

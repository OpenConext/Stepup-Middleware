<?php

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData;

interface RightToObtainDataInterface
{
    /**
     * Obtains the user data, suitable for showing a user that exercised its
     * right to obtain user data.
     */
    public function obtainUserData(): array;

    /**
     * Retrieve the list of allowed data to retireve from the event.
     * Some data which is irrelevant for later reference is not included
     * on the allowlist. Some examples of data not on the allowlist are
     * a registration nonce, or a registration code.
     */
    public function getAllowlist(): array;
}

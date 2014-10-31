<?php

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Exception;

interface ProcessingAbortedException
{
    /**
     * @return string[]
     */
    public function getErrors();
}

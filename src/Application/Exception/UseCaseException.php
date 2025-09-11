<?php

namespace App\Application\Exception;

class UseCaseException extends ApplicationException
{
    public function __construct(string $message = 'Failed to fetch rates')
    {
        parent::__construct($message);
    }
}

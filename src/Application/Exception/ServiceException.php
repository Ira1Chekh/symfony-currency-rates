<?php

namespace App\Application\Exception;

class ServiceException extends \RuntimeException
{
    public function __construct(string $message, int $code = 500)
    {
        parent::__construct($message, $code);
    }
}

<?php

namespace App\Application\Exception;

class ApplicationException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}

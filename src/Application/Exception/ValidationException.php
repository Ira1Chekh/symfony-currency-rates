<?php

namespace App\Application\Exception;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(string $message = 'Validation failed', array $errors = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

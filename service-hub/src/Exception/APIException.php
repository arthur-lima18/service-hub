<?php

namespace App\Exception;

class APIException extends \Exception
{
    public function __construct(
        string $message,
        private int $statusCode = 400
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
<?php

namespace Core\Exceptions;

class ForbiddenException extends \Exception
{
    public function __construct(string $message = 'Geen toegang')
    {
        parent::__construct($message, 403);
    }
}

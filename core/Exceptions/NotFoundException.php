<?php

namespace Core\Exceptions;

class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Pagina niet gevonden')
    {
        parent::__construct($message, 404);
    }
}

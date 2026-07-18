<?php

namespace App\Exceptions;

class BlockedAccountException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Conta desactivada.');
    }
}

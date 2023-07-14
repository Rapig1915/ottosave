<?php

namespace App\Exceptions;

use Exception;

class SubscriptionExpiredException extends Exception
{

    public function __construct($message, $code = null)
    {
        parent::__construct($message, $code);
    }
}

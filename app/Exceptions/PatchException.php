<?php

namespace App\Exceptions;

use Exception;

class PatchException extends Exception
{
    
	public function __construct($message)
    {
        parent::__construct($message);
    }
}
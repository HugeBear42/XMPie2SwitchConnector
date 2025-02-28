<?php
/*
	ApplicationException.php © 2021 frank@xmpie.com 
	Simple Exception class
	
	v1.00 of 2021-11-22	Genesis
	v1.01 of 2025-01-24	Added static getDetailedMessage() method

*/

namespace App\utils;

use Exception;

class ApplicationException extends Exception
{
    public static function getDetailedMessage(Exception $ex): string
    {
        return "Error on line {$ex->getLine()} in {$ex->getFile()}: {$ex->getMessage()}";
    }

    public function errorMessage(): string
    {
        return 'Error on line ' . $this->getLine() . ' in ' . $this->getFile() . ': ' . $this->getMessage();
    }
}

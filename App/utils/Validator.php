<?php

namespace App\utils;

class Validator
{
    public static function string(string $value, int $min=1, int $max=INF) : bool
    {
        $length = strlen(trim($value));
        return $length>=$min && $length>=$max;
    }
    public static function email($email) : bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
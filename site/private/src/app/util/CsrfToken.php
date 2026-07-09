<?php

namespace App\Util;

class CsrfToken
{
    public static function generate(): string
    {
        if (empty($_SESSION['CSRF_TOKEN'])) {
            $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['CSRF_TOKEN'];
    }

    public static function verify(?string $token): bool
    {
        if (empty($_SESSION['CSRF_TOKEN']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['CSRF_TOKEN'], $token);
    }

    public static function regenerate(): string
    {
        $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        return $_SESSION['CSRF_TOKEN'];
    }
}
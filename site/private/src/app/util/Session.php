<?php 
namespace App\Util;

class Session
{
    private const FLASH_KEY = '_flash';

    private function __construct() {}
    private function __clone() {}

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        if (isset($_SESSION[self::FLASH_KEY][$key])) {
            $value = $_SESSION[self::FLASH_KEY][$key];
            unset($_SESSION[self::FLASH_KEY][$key]);
            return $value;
        }
        return $default;
    }

    public static function save(): void
    {
        session_write_close();
    }
}
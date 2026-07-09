<?php 
namespace App\Util;

use App\Util\Log;

class Cache
{
    private static string $cacheDir;

    private function __construct() {}
    private function __clone() {}

    private static function getCacheDir(): string
    {
        if (!isset(self::$cacheDir)) {
            $dir = DIR_CACHE;
            
            if (!is_dir($dir)) {
                mkdir($dir, 0770, true);
            }
            
            self::$cacheDir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return self::$cacheDir;
    }

    private static function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
        return self::getCacheDir() . $safeKey . '.cache';
    }

    public static function set(string $key, mixed $value, int $ttlInSeconds = 3600): bool
    {
        try {
            $filePath = self::getFilePath($key);
            
            $data = [
                'expires_at' => time() + $ttlInSeconds,
                'value' => $value
            ];

            $serializedData = serialize($data);

            return file_put_contents($filePath, $serializedData, LOCK_EX) !== false;
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::set", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function replace(string $key, mixed $value, int $ttlInSeconds = 3600): bool
    {
        if (!self::has($key)) {
            return false;
        }
        return self::set($key, $value, $ttlInSeconds);
    }

    public static function get(string $key): mixed
    {
        try {
            $filePath = self::getFilePath($key);

            if (!file_exists($filePath)) {
                return false;
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                return false;
            }

            $data = unserialize($content);
            
            if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
                return false;
            }

            if (time() > $data['expires_at']) {
                self::delete($key);
                return false;
            }

            return $data['value'];
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::get", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function delete(string $key): bool
    {
        try {
            $filePath = self::getFilePath($key);
            if (file_exists($filePath)) {
                return unlink($filePath);
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::delete", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== false;
    }

    public static function flush(): bool
    {
        try {
            $files = glob(self::getCacheDir() . '*.cache');
            $success = true;
            
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file) && !unlink($file)) {
                        $success = false;
                    }
                }
            }
            return $success;
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::flush", 'main.log', $exception->getMessage());
            return false;
        }
    }
    /*private static ?\Memcached $connection = null;

    private function __construct() {}
    private function __clone() {}

    private static function getConnection(): \Memcached
    {
        if (is_null(self::$connection)) {
            
            $host = $_ENV['MEMCACHE_HOST'] ?? 'memcached';
            $port = (int)($_ENV['MEMCACHE_PORT'] ?? 11211);
            
            self::$connection = new \Memcached();

            self::$connection->addServer($host, $port);
        }

        return self::$connection;
    }

    public static function set(string $key, mixed $value, int $ttlInSeconds = 3600): bool
    {
        try {
            return self::getConnection()->set($key, $value, $ttlInSeconds);
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::set", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function replace(string $key, mixed $value, int $ttlInSeconds = 3600): bool
    {
        try {
            return self::getConnection()->replace($key, $value, $ttlInSeconds);
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::replace", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function get(string $key): mixed
    {
        try {
            return self::getConnection()->get($key);
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::get", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function delete(string $key): bool
    {
        try {
            return self::getConnection()->delete($key);
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::delete", 'main.log', $exception->getMessage());
            return false;
        }
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== false;
    }

    public static function flush(): bool
    {
        try {
            return self::getConnection()->flush();
        } catch (\Exception $exception) {
            Log::error("Erro em Cache::flush", 'main.log', $exception->getMessage());
            return false;
        }
    }*/
}
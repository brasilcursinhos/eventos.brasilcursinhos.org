<?php
namespace App\Util;

class Jwt
{
    private static function getSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');

        if (empty($secret)) {
            throw new \Exception("A chave JWT_SECRET não está configurada no ambiente.");
        }

        return (string) $secret;
    }

    public static function encode(array $payload): string
    {
        $secret = self::getSecret();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (7 * 24 * 60 * 60);
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decode(string $token): ?array
    {
        $secret = self::getSecret();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $validSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if (!hash_equals($validSignatureEncoded, $signature)) {
            return null;
        }

        $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }

        return $decodedPayload;
    }
}
<?php 
namespace App\Util;

class Crypto 
{
    private static string $hashKey;
    private static string $encKey;
    //private static string $fileKey;

    private function __construct() {}

    public static function init(): void
    {
        if(!isset(self::$hashKey)){
            self::$hashKey = base64_decode($_ENV['APP_KEY_HASH']);
        }

        if(!isset(self::$encKey)){
            self::$encKey = base64_decode($_ENV['APP_KEY_ENC']);
        }

        /*if(!isset(self::$fileKey)){
            self::$fileKey = base64_decode($_ENV['APP_KEY_FILE']);
        }*/
    }

    public static function hash(string $message, int $length = SODIUM_CRYPTO_GENERICHASH_BYTES): string
    {
        self::init();

        return sodium_crypto_generichash($message, self::$hashKey, $length);
    }

    public static function encrypt(string $message, string $aad = '') : string
    {
        self::init();

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($message, $aad, $nonce, self::$encKey);
        // Payload bin: payload version(1) + key version(1) + nonce + cipher
        return pack('CC', 1, 1) . $nonce . $cipher;
    }

    public static function decrypt(string $payload, string $aad = ''): string|false
    {
        self::init();
        
        //$header = substr($payload, 0, 2);
        //[$payloadVersion, $keyVersion] = array_values(unpack('Cversion/Ckid', $header));

        $nonceSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = substr($payload, 2, $nonceSize);
        $cipher = substr($payload, 2 + $nonceSize);
        return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, self::$encKey);
    }
}
<?php 
namespace App\Util;

use DateTime;
use DateTimeZone;

class Log {

    private const EMERGENCY = 'EMERGENCY';
    private const ALERT     = 'ALERT';
    private const CRITICAL  = 'CRITICAL';
    private const ERROR     = 'ERROR';
    private const WARNING   = 'WARNING';
    private const NOTICE    = 'NOTICE';
    private const INFO      = 'INFO';
    private const DEBUG     = 'DEBUG';

    private static function writeLog(string $message, string $level, string $file, string $additionalErrorMessage)
    {
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $now = new DateTime('now', $timezone);
        $date = $now->format('Y/m/d H:i:s');

        $log = sprintf("[%s] %s: %s%s", $date, $level, $message, PHP_EOL);

        $file = DIR_LOGS . $file;

        file_put_contents($file, $log, FILE_APPEND);

        if(!empty($additionalErrorMessage)) {
            
            $log = sprintf("                      ADDITIONAL MESSAGE: %s%s", $additionalErrorMessage, PHP_EOL);

            file_put_contents($file, $log, FILE_APPEND);
        }
    }

    public static function emergency(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::EMERGENCY, $file, $additionalErrorMessage);
    }

    public static function alert(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::ALERT, $file, $additionalErrorMessage);
    }

    public static function critical(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::CRITICAL, $file, $additionalErrorMessage);
    }

    public static function error(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::ERROR, $file, $additionalErrorMessage);
    }

    public static function warning(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::WARNING, $file, $additionalErrorMessage);
    }

    public static function notice(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::NOTICE, $file, $additionalErrorMessage);
    }

    public static function info(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::INFO, $file, $additionalErrorMessage);
    }

    public static function debug(string $message, $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, self::DEBUG, $file, $additionalErrorMessage);
    }

    public static function log(string $message, $level = 'INFO', $file='main.log', $additionalErrorMessage = '')
    {
        self::writeLog($message, strtoupper($level), $file, $additionalErrorMessage);
    }
}
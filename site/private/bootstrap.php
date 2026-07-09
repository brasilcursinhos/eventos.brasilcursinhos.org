<?php
    require_once __DIR__ . '/config/settings.php';

    ini_set('default_charset', 'utf-8');
    ini_set('date.timezone', 'America/Sao_Paulo');
    ini_set('memory_limit', '256M');

    ini_set('zend.assertions', PRODUCTION_MODE ? -1 : 1);

    //ini_set('session.save_handler', 'memcached');
    //ini_set('session.save_path', 'memcached:11211');
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', realpath(DIR_SESSIONS));
    ini_set('session.serialize_handler', 'igbinary');
    ini_set('session.cache_limiter', 'nocache');
    ini_set('session.gc_probability', 0);
    ini_set('session.gc_maxlifetime', 14400);
    /*ini_set('memcached.sess_locking', 1); 
    ini_set('memcached.sess_lock_wait_min', '50');
    ini_set('memcached.sess_lock_wait_max', '50');
    ini_set('memcached.sess_lock_retries', '100');
    ini_set('memcached.sess_persistent', 1);
    ini_set('memcached.sess_binary_protocol', 1);*/

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if ($isHttps) {
        $sessionName = "__Host-EVENTOSBCSESSIONID";
        $cookieSecure = true;
    } else {
        $sessionName = "EVENTOSBCSESSIONID";
        $cookieSecure = false;
    }

    session_name($sessionName);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => null, 
        'secure' => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start([
        'use_strict_mode' => 0,
    ]);

    if (session_status() === PHP_SESSION_ACTIVE && empty(session_id())) {
        throw new \Exception("Falha crítica: session_start() não iniciou.");
    }

    if (ob_get_level() === 0) {
        ob_start(); 
    }
    
    error_reporting(E_ALL);
    ini_set('display_errors', PRODUCTION_MODE ? false : true);
    ini_set('log_errors', 1);
    ini_set('error_log', realpath(__DIR__ . '/logs/') . '/php.log');
    ini_set('ignore_repeated_errors', 1);
    ini_set('ignore_repeated_source', 1);
    ini_set('log_errors_max_len', 1024);

    require __DIR__ . '/vendor/autoload.php';

use App\Util\Cache;
use Dotenv\Dotenv;
    use DI\ContainerBuilder;
    use App\Util\DatabaseProvider;
    
    $dotenv = Dotenv::createImmutable(DIR_SECRETS);
    $dotenv->load();

    $builder = new ContainerBuilder();

    $builder->addDefinitions([
        PDO::class => function() {
            return DatabaseProvider::getConnection();
        }
    ]);

    return $builder->build();

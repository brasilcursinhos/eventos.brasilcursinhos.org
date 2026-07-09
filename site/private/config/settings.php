<?php 
    include_once 'local-settings.php';
    
    define('DIR_TEMPLATES', realpath(__DIR__ . '/../src/view/templates/') . '/');
    define('DIR_EMAILS', realpath(__DIR__ . '/../src/view/emails/') . '/');
    define('DIR_PUBLIC_HTML', realpath(__DIR__ . '/../../public_html/') . '/');
    define('DIR_SECRETS', realpath(__DIR__ . '/secrets/') . '/');
    define('DIR_PRIVATE_DOCUMENTS', realpath(__DIR__ . '/../storage/documents/') . '/');
    define('DIR_PUBLIC_DOCUMENTS', realpath(__DIR__ . '/../../public_html/documentos/') . '/');
    define('DIR_LOGS', realpath(__DIR__ . '/../logs/') . '/');
    define('DIR_CACHE', realpath(__DIR__ . '/../storage/cache/') . '/');
    define('DIR_SESSIONS', realpath(__DIR__ . '/../storage/sessions/') . '/');
    define('SITE_HTTP_HOST', 'brasilcursinhos.org');

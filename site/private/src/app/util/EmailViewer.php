<?php 
namespace App\Util;

use Twig;

class EmailViewer {

    private static ?\Twig\Environment $viewer;

    private function __construct() {}
    private function __clone() {}

    public static function getTwig()
    {
        if(empty(self::$viewer)){
            $loader = new \Twig\Loader\FilesystemLoader(DIR_EMAILS);
            $loader->addPath(DIR_EMAILS.'html/', 'html');
            $loader->addPath(DIR_EMAILS.'text/', 'text');
            if(PRODUCTION_MODE) {
                $cache = [
                    'cache' => DIR_EMAILS . 'cache',
                    'auto_reload' => true,
                    'debug' => false
                ];
            } else {
                $cache = [
                    'cache' => false,
                    'auto_reload' => true,
                    'debug' => true
                ];
            }
            self::$viewer = new \Twig\Environment($loader, $cache);
            self::$viewer->getExtension(\Twig\Extension\CoreExtension::class)->setTimezone('America/Sao_Paulo');
        }
        return self::$viewer;
    }

    public static function getHtml(string $pageName, $parameters = [])
    {
        $template = self::getTwig()->load('@html/' . $pageName);
        return $template->render($parameters);
    }

    public static function getText(string $pageName, $parameters = [])
    {
        $template = self::getTwig()->load('@text/' . $pageName);
        return $template->render($parameters);
    }
}
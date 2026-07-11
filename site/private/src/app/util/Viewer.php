<?php 
namespace App\Util;

use App\Enum\Role\UserRole;

class Viewer {

    private static ?\Twig\Environment $viewer;

    private function __construct() {}
    private function __clone() {}

    public static function getTwig()
    {
        if(empty(self::$viewer)){
            $loader = new \Twig\Loader\FilesystemLoader(DIR_TEMPLATES);
            $loader->addPath(DIR_TEMPLATES.'access/', 'access');
            $loader->addPath(DIR_TEMPLATES.'components/', 'components');
            $loader->addPath(DIR_TEMPLATES.'events/', 'events');
            $loader->addPath(DIR_TEMPLATES.'links/', 'links');
            $loader->addPath(DIR_TEMPLATES.'public/', 'public');
            $loader->addPath(DIR_TEMPLATES.'restricted/', 'restricted');
            $loader->addPath(DIR_TEMPLATES.'restricted/admin/', 'admin');
            $loader->addPath(DIR_TEMPLATES.'restricted/participant/', 'participant');
            if(PRODUCTION_MODE) {
                $cache = [
                    'cache' => DIR_TEMPLATES . 'cache',
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

            $csrfFunction = new \Twig\TwigFunction('csrf_field', function () {
                $token = CsrfToken::generate();
                return '<input type="hidden" name="csrf-token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
            }, ['is_safe' => ['html']]);

            self::$viewer->addFunction($csrfFunction);

            $roleFunction = new \Twig\TwigFunction('has_role', function ($role) {
                return \App\Util\Auth::hasRole($role);
            });
            self::$viewer->addFunction($roleFunction);
            self::$viewer->addGlobal('ROLES', [
                'ADMIN' => UserRole::ADMINSTRATOR,
                'BC_MEMBER' => UserRole::BC_MEMBER,
                'EVENT_PARTICIPANT' => UserRole::EVENT_PARTICIPANT
            ]);
        }
        return self::$viewer;
    }

    public static function render(string $pageName, array $parameters = [])
    {
        $template = self::getTwig()->load($pageName);
        return $template->render($parameters);
    }
}
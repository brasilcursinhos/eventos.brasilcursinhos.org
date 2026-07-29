<?php 
namespace App\Controller;

use App\Enum\Role\UserRole;
use App\Repository\MembersRepository;
use App\Service\ValidatorService;
use App\Util\Auth;
use App\Util\Session;
use Router\Request;
use Router\Response;

class AdministratorController
{
    private array $links;

    public function __construct()
    {
        $this->links =  array(
            (object) array('name' => 'Página inicial', 'url' => '/administrador'),
            (object) array('name' => 'Conferir/Atualizar Cadastro', 'url' => '/administrador/entrevistas'),
            (object) array('name' => 'Conciliação de pagamentos', 'url' => '/administrador/agendamentos'),
            (object) array('name' => 'Lista de Participantes', 'url' => '/administrador/membros')
        );

        if(Auth::hasRole(UserRole::BC_MEMBER)) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página de Membro', 'url' => '/membro')
                ]
            );
        }

        if(Auth::hasRole(UserRole::members())) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página de Participante', 'url' => '/participante')
                ]
            );
        }
    }

    public function showInfoPage()
    {
        ob_start();
        phpinfo();
        $html_completo = ob_get_clean();

        $dom = new \DOMDocument();
        // O prefixo abaixo evita problemas com caracteres especiais/acentuação
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_completo);

        // Captura o CSS original (sem alterações)
        $style_tags = $dom->getElementsByTagName('style');
        $css_original = ($style_tags->length > 0) ? $style_tags->item(0)->textContent : "";

        // Captura o conteúdo do Body
        $body = $dom->getElementsByTagName('body')->item(0);
        $body_inner = "";
        foreach ($body->childNodes as $child) {
            $body_inner .= $dom->saveHTML($child);
        }

        return Response::html('@admin/php-info.html', ['links' => $this->links, 'css' => $css_original, 'content' => $body_inner])->withoutCSP();
    }
    
    public function showHomePage(): Response
    {
        return Response::html('@admin/home.html', ['user' => Auth::user(), 'links' => $this->links])->withoutCache();
    }

}
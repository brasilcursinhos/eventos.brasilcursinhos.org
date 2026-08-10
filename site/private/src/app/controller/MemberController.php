<?php 
namespace App\Controller;

use App\Enum\Role\UserRole;
use App\Enum\Status\SystemStatus;
use App\Service\MemberService;
use Router\Response;
use Router\Request;
use App\Util\Auth;
use App\Util\Session;

class MemberController
{
    private array $links;
    private MemberService $service;

    public function __construct(MemberService $service)
    {
        $this->service = $service;

        $this->links =  array(
            (object) array('name' => 'Página inicial', 'url' => '/membro'),
            (object) array('name' => 'Atualizar CSV de transações', 'url' => '/membro/update-transacoes'),
        );

        if(Auth::hasRole(UserRole::EVENT_PARTICIPANT)) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página de Participante', 'url' => '/participante')
                ]
            );
        }

        if(Auth::hasRole(UserRole::ADMINSTRATOR)) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página do Administrador', 'url' => '/administrador')
                ]
            );
        }
    }

    public function showHomePage(): Response
    {
        return Response::html('@member/home.html', ['links' => $this->links, 'user' => Auth::user()])->withoutCache();
    }

    public function showUpdateTransactionsPage(): Response
    {
        $updateTransactionsStatusValue = Session::getFlash('updateTransactionsStatusValue');

        $data = [
            'links' => $this->links,
        ];

        if(!is_null($updateTransactionsStatusValue)) {
            $data['updateTransactionsStatus'] = SystemStatus::tryFrom($updateTransactionsStatusValue);
        }

        return Response::html('@member/update-transactions.html', $data)->withoutCache();
    }

    public function updateTransactions(Request $request): Response
    {
        $result = $this->service->updateTransactions($request);

        Session::flash('updateTransactionsStatusValue', $result->status->value);

        return Response::redirect('/membro/update-transacoes', 303);
    }
}
<?php 
namespace App\Controller;

use App\Enum\Role\UserRole;
use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Status\FinancialTransactionStatus;
use App\Repository\AdministratorRepository;
use App\Repository\EventsRepository;
use App\Service\EventsService;
use App\Service\ValidatorService;
use App\Util\Auth;
use Router\Request;
use Router\Response;

class AdministratorController
{
    private array $links;
    private AdministratorRepository $repository;

    public function __construct(AdministratorRepository $repository)
    {
        $this->repository = $repository;

        $this->links =  array(
            (object) array('name' => 'Página inicial', 'url' => '/administrador'),
            (object) array('name' => 'Cadastrar inscrição', 'url' => '/administrador/encup'),
            (object) array('name' => 'Conferir transações', 'url' => '/administrador/transacoes'),
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

    public function showEncupPage(): Response
    {
        return Response::html('@admin/registration.html', ['user' => Auth::user(), 'links' => $this->links])->withoutCache();
    }

    public function showPaymentPage(Request $request, EventsRepository $repository): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        if($cpf) {
            
            $user = $this->repository->getUser($cpf);
            if($user) {
                $event = $repository->getEvent(1);
                $registration = $repository->getEventRegistration($event, $user);
                
                if($registration?->status === EventRegistrationStatus::PENDING_PAYMENT) {
                    $ticket = $repository->getEventTicket($registration->ticketId);
                    //$amountForPix = number_format((float) $registration->amountDue, 2, '.', '');
                    
                    /*$payload = new StaticPayload();
                    $payload->setPixKey(Parser::KEY_TYPE_EMAIL, 'financeiro@brasilcursinhos.org')
                        ->setAmount($amountForPix)
                        ->setTid('ENCUP2026'.$ticket->id)
                        ->setDescription('Ingresso ' . strstr($ticket->name, '-', true) . $ticket->type->label())
                        ->setMerchantName('Brasil Cursinhos')
                        ->setMerchantCity('Sao Paulo');
                    $pixCode = $payload->getPixCode();
                    $pixQRCode = $payload->getQRCode();*/
                    return Response::html('@participant/encup/payment.html', [ 'links' => $this->links, 
                        'user' => $user, 'ticket' => $ticket, 'registration' => $registration, 'event' => $event, 'pixCode' => '$pixCode', 'pixQRCode' => '$pixQRCode'
                        ])->withoutCache();
                } else {
                    return Response::html('@admin/payment-existence.html', [ 'links' => $this->links])->withoutCache();
                }
            }else {
                return Response::html('@admin/user-404.html', [ 'links' => $this->links])->withoutCache();
            }
            
        }else {
            return Response::html('@admin/invalid-cpf.html', [ 'links' => $this->links])->withoutCache();
        }
    }

    public function showSocialRequestPage(Request $request, EventsRepository $repository): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        if($cpf) {
            
            $user = $this->repository->getUser($cpf);
            if($user) {
                $event = $repository->getEvent(1);
                $registration = $repository->getEventRegistration($event, $user);
                
                if($registration?->status === EventRegistrationStatus::PENDING_PAYMENT) {
                    $ticket = $repository->getEventTicket($registration->ticketId);
                    //$amountForPix = number_format((float) $registration->amountDue, 2, '.', '');
                    
                    /*$payload = new StaticPayload();
                    $payload->setPixKey(Parser::KEY_TYPE_EMAIL, 'financeiro@brasilcursinhos.org')
                        ->setAmount($amountForPix)
                        ->setTid('ENCUP2026'.$ticket->id)
                        ->setDescription('Ingresso ' . strstr($ticket->name, '-', true) . $ticket->type->label())
                        ->setMerchantName('Brasil Cursinhos')
                        ->setMerchantCity('Sao Paulo');
                    $pixCode = $payload->getPixCode();
                    $pixQRCode = $payload->getQRCode();*/
                    return Response::html('@participant/encup/social-request.html', [ 'links' => $this->links, 
                        'user' => $user, 'ticket' => $ticket, 'registration' => $registration, 'event' => $event])->withoutCache();
                } else {
                    return Response::html('@admin/payment-existence.html', [ 'links' => $this->links])->withoutCache();
                }
            }else {
                return Response::html('@admin/user-404.html', [ 'links' => $this->links])->withoutCache();
            }
            
        }else {
            return Response::html('@admin/invalid-cpf.html', [ 'links' => $this->links])->withoutCache();
        }
    }

    public function showRegistrationPage(Request $request, EventsRepository $repository): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        if($cpf) {
            
            $user = $this->repository->getUser($cpf);

            if($user) {
                $event = $repository->getEvent(1);
                $registration = $repository->getEventRegistration($event, $user);

                if(is_null($registration)) {

                    $tickets = $repository->getEventTickets($event->id);

                    return Response::html('@participant/encup/registration.html', [ 'links' => $this->links, 
                    'user' => $user, 'tickets' => $tickets, 'registrationType' => 'normal', 'event' => $event, 'exceptionalRegistration' => false
                    ])->withoutCache();
                } else {
                    return Response::html('@admin/registration-existence.html', [ 'links' => $this->links])->withoutCache();
                }
            }else {
                return Response::html('@admin/user-404.html', [ 'links' => $this->links])->withoutCache();
            }
            
        }else {
            return Response::html('@admin/invalid-cpf.html', [ 'links' => $this->links])->withoutCache();
        }
        
    }

    public function showBatchPaymentPage(string $amount, EventsRepository $repository) {
        $amount = ValidatorService::validateInt($amount);
        $tickets = $repository->getEventTickets(1);
        return Response::html('@admin/batch-payment.html', [ 'links' => $this->links, 'amount' => $amount, 'tickets' => $tickets])->withoutCache();
    }

    public function saveBatchPayment(string $amount, Request $request, EventsService $service): Response
    {
        $amount = ValidatorService::validateInt($amount);
        $result = $service->saveBatchPayment($request, $this->repository, $amount);
        
        var_dump($result);
        echo "<br><a href='/administrador/encup'>Voltar</a>";
        exit;
    }

    public function showTransactionsPage(EventsRepository $repository, ?string $code = null): Response
    {
        $transactions = $repository->getTransactions(is_null($code)? false:true);
        return Response::html('@admin/transactions.html', [ 'links' => $this->links, 'transactions' => $transactions])->withoutCache();
    }

    public function reconcileTransactions(EventsRepository $repository): Response
    {
        $transactions = $repository->getTransactions(true);
        foreach($transactions as $transaction) {
            $bcAccount = $repository->getBcAccountTransaction($transaction->providerTransactionId);
            if($bcAccount) {
                if(bccomp($bcAccount->amount, $transaction->totalAmount) === 0) {
                    $newTransaction = $transaction->updateStatus(FinancialTransactionStatus::APPROVED);
                } else {
                    $newTransaction = $transaction->updateStatus(FinancialTransactionStatus::REJECTED);
                }
                $repository->updateTransactionStatus($newTransaction);
            }
        }
        return Response::redirect('/administrador/transacoes/pendentes', 303);
    }

    public function updateTransactionId(Request $request, EventsRepository $repository): Response
    {
        $userId = ValidatorService::validateInt($request->__get('user-id'));
        $transactionId = ValidatorService::validateInt($request->__get('transaction-id'));
        $providerTransactionId = ValidatorService::validateString($request->__get('provider-id'));
        if($userId && $providerTransactionId && $transactionId) {
            $repository->updateTransactionId($transactionId, $providerTransactionId, $userId);
        }
        
        return Response::redirect('/administrador/transacoes/pendentes', 303);
    }

    public function saveSocialRequest(Request $request, EventsService $service): Response
    {
        $result = $service->saveSocialRequest($request);
        
        var_dump($result);
        echo "<br><a href='/administrador/encup'>Voltar</a>";
        exit;
    }

}
<?php 
namespace App\Controller;

use App\Enum\Role\UserRole;
use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Status\FinancialTransactionStatus;
use App\Enum\Type\EventRegistrationType;
use App\Model\EmergencyData;
use App\Model\EventRegistration;
use App\Model\PersonalData;
use App\Repository\AccessRepository;
use App\Repository\AdministratorRepository;
use App\Repository\EventsRepository;
use App\Repository\FileRepository;
use App\Service\EventsService;
use App\Service\ValidatorService;
use App\Util\Auth;
use App\Util\FileManager;
use App\Util\Log;
use App\Util\Session;
use Google\Type\PhoneNumber;
use Router\Request;
use Router\Response;
use App\Model\Event;

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
            (object) array('name' => 'Transações da conta', 'url' => '/administrador/transacoes-conta')
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
        $lastCpf = Session::get('lastCpf');
        return Response::html('@admin/registration.html', ['user' => Auth::user(), 'links' => $this->links, 'lastCpf' => $lastCpf])->withoutCache();
    }

    public function showPaymentPage(Request $request, EventsRepository $repository): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        if($cpf) {
            
            $user = $this->repository->getUser($cpf);
            if($user) {
                Session::set('lastCpf', $cpf);
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
                Session::set('lastCpf', $cpf);
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
                Session::set('lastCpf', $cpf);
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

    public function showStatusPage(Request $request, EventsRepository $repository): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        if($cpf) {
            
            $user = $this->repository->getUser($cpf);

            if($user) {
                Session::set('lastCpf', $cpf);
                $event = $repository->getEvent(1);
                $registration = $repository->getEventRegistration($event, $user);

                if(!is_null($registration)) {

                    $ticket = $repository->getEventTicket($registration->ticketId);

                    return Response::html('@participant/encup/status.html', [ 'links' => $this->links, 
                        'user' => $user, 'ticket' => $ticket, 'registration' => $registration, 'event' => $event
                    ])->withoutCache();
                } else {
                    return Response::html('@admin/registration-404.html', [ 'links' => $this->links])->withoutCache();
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

    public function showAccountTransactionsPage(EventsRepository $repository, ?string $code = null): Response
    {
        $transactions = $repository->getAccountTransactions(is_null($code)? false:true);
        return Response::html('@admin/account-transactions.html', [ 'links' => $this->links, 'transactions' => $transactions])->withoutCache();
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

    public function showProof(Request $request, FileRepository $repository): Response
    {
        $fileId = ValidatorService::validateInt($request->__get('file-id'));
        $userId = ValidatorService::validateInt($request->__get('user-id'));
        if(!$fileId || !$userId) {
            throw new \Router\Exception\RouteNotFound();
        }
        $file = $repository->getFile($fileId);
        if(is_null($file)) {
            throw new \Router\Exception\RouteNotFound();
        }
        $content = FileManager::readFromDisk($file, 'USER_ID_'.$userId);
        return Response::file($content, $file->originalName, $file->mimeType);
    }

    public function insertUsers(AccessRepository $repository): Response
    {
        $filePath = __DIR__ . '/user-encup.csv';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("O arquivo CSV não foi encontrado: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir o arquivo CSV: {$filePath}");
        }

        // Lê a primeira linha (cabeçalhos)
        $headers = fgetcsv($handle, 0, ';', '"', '\\');
        
        // Remove o BOM (Byte Order Mark) do primeiro cabeçalho, comum em arquivos exportados do Windows/Excel
        if ($headers !== false) {
            $headers[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', $headers[0]);
        }

        $personalDataList = [];

        // Lê as linhas subsequentes
        while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            // Ignora linhas totalmente vazias
            if (empty(array_filter($row))) {
                continue;
            }

            // Mapeia a linha atual com as chaves do cabeçalho
            $data = array_combine($headers, $row);

            $cpf = ValidatorService::validateCpf($data['cpf']);
            $email = ValidatorService::validateEmail($data['email']);

            if(!$cpf || !$email) {
                Log::error('CPF/E-mail inválido no id: '.$data['id'], 'insert-errors.log');
                continue;
            }

            $personalDataList[] = new PersonalData(
                fullName: ValidatorService::validatePersonalName($data['fullName']),
                useSocialName: false,
                nickname: ValidatorService::validatePersonalName($data['nickname']),
                pronouns: [$data['pronouns']],
                genderIdentity: $data['genderIdentify'],
                ethnicity: $data['ethnicity'],
                cpf: $cpf,
                birthDate: new \DateTimeImmutable($data['birthDate']),
                email: $data['email'],
                phone: $data['phone'],
                socialName: null,
                address: null
            );
        }

        fclose($handle);

        foreach($personalDataList as $personalData) {
            try {
                $repository->saveRegistrationn($personalData);
            } catch (\Exception $exception) {
                Log::error("Erro na inserção: ", 'inserts.log', $exception->getMessage());
                Log::error('Insert com erro: ', 'insert-errors.log', json_encode($personalData));
                continue;
            }
        }

        echo "Sucesso";exit;

        return Response::empty();
    }

    public function fixCpf(AdministratorRepository $repo)
    {
        $users = $repo->getAllUsers();
        foreach($users as $user) {
            try {
                $repo->updateUserCpf(ValidatorService::validateCpf($user->personalData->cpf), $user->id);
            } catch(\Exception $e) {
                continue;
            }
        }
        echo "sucesso";
        exit;
    }

    public function insertRegistrations(EventsRepository $erepo, AdministratorRepository $arepo): Response
    {
        $filePath = __DIR__ . '/registration.csv';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("O arquivo CSV não foi encontrado: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir o arquivo CSV: {$filePath}");
        }

        // Lê a primeira linha (cabeçalhos)
        $headers = fgetcsv($handle, 0, ';', '"', '\\');
        
        // Remove o BOM (Byte Order Mark) do primeiro cabeçalho, comum em arquivos exportados do Windows/Excel
        if ($headers !== false) {
            $headers[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', $headers[0]);
        }


        // Lê as linhas subsequentes
        while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            // Ignora linhas totalmente vazias
            if (empty(array_filter($row))) {
                continue;
            }

            // Mapeia a linha atual com as chaves do cabeçalho
            $data = array_combine($headers, $row);

            $cpf = ValidatorService::validateCpf($data['cpf']);
            $ticketId = ValidatorService::validateInt($data['ticketId']);

            if(!$cpf) {
                Log::error('CPF inválido no id: '.$data['cpf'], 'registrations-errors.log');
                continue;
            }

            $ticket = $erepo->getEventTicket($ticketId);
            $user = $arepo->getUser($cpf);
            $event = $erepo->getEvent(1);

            if(is_null($ticket) || is_null($user) || is_null($event)) {
                Log::error('Erro ao recuperar dados iniciais: '.$cpf, 'registrations-errors.log');
                continue;
            }

            if(!is_null($erepo->getEventRegistration($event, $user))) {
                Log::error('Inscrição existente: '.$cpf, 'registrations-errors.log');
                continue;
            }

            $emergencyData = new EmergencyData(
                name: 'Alguém',
                kinship: 'Outro',
                phone: '12345678901'
            );

            $registration = new EventRegistration(
                id: null,
                registration: null,
                type: ($data['type'] == 'filiado')? EventRegistrationType::AFFILIATED_CUP_MEMBER:EventRegistrationType::UNAFFILIATED_CUP_MEMBER,
                status: EventRegistrationStatus::PENDING_PAYMENT,
                basePrice: $ticket->price,
                amountDue:$ticket->price,
                userId: $user->id,
                eventId: $event->id,
                ticketId: $ticket->id,
                emergencyData: $emergencyData,
                additionalData: ['dietaryRestrictions' => ['Nenhuma restrição alimentar']],
                organizationName: $data['organizationName']
            );

            if(bccomp('0.00', $ticket->price) === 0) {
                $registration = $registration->updateStatus(EventRegistrationStatus::CONFIRMED);
                // email de confirmação
            }
            
            $attemptsInsert = 0;

            while($attemptsInsert < 5) {

                $registration = $registration->withRegistration($this->generateRegistration($event));
                
                try {

                    $insertedRegistration = $erepo->saveEventRegistration($registration);
                
                    break;

                } catch(\PDOException $exception) {

                    $errorCode = $exception->errorInfo[1] ?? 0;
                    $errorMessage = $exception->getMessage();
                    $message = null;

                    if ($errorCode === 1062) {
                        if(str_contains($errorMessage, 'idxEventRegistrationsRegistrationHash')) {
                            $attemptsInsert++;
                            continue;
                        }
                    }

                    Log::error('Registro duplicado de inscricao gerado: '.$cpf, 'registrations-errors.log');

                } catch(\Exception $exception) {

                    Log::error('Erro ao gravar inscricao: '.$cpf, 'registrations-errors.log');
                }
            }

            if(!isset($insertedRegistration) || is_null($insertedRegistration)) {
                Log::error('Erro ao gravar inscricao 2: '.$cpf, 'registrations-errors.log');
            } else {
                $registration = $insertedRegistration;
            }

            //email confirmacao
        }

        fclose($handle);

        echo "Sucesso";exit;

        return Response::empty();
    }

    private function generateRegistration(Event $event): string
    {
        return substr($event->year, -2) . '1' . $event->type->value . Auth::getRandomCode(4, true);
    }
}

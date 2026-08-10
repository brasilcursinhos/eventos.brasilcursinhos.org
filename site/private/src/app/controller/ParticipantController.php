<?php 
namespace App\Controller;

use App\Enum\Role\UserRole;
use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Status\SystemStatus;
use App\Repository\EventsRepository;
use App\Service\EventsService;
use Router\Response;
use App\Util\Auth;
use Piggly\Pix\StaticPayload;
use App\Util\Session;
use Piggly\Pix\Parser;
use Router\Request;

class ParticipantController
{
    private array $links;
    private EventsService $service;
    private EventsRepository $repository;

    public function __construct(EventsService $service, EventsRepository $repository)
    {
        $this->service = $service;
        $this->repository = $repository;

        $this->links =  array(
            (object) array('name' => 'Página inicial', 'url' => '/participante'),
        );

        if(Auth::hasRole(UserRole::BC_MEMBER)) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página de Membro', 'url' => '/membro')
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
        return Response::html('@participant/home.html', ['links' => $this->links, 'user' => Auth::user()])->withoutCache();
    }

    public function showEncupRegistrationPage(?string $code = null): Response
    {   
        $saveRegistrationStatusValue = Session::get('saveRegistrationStatusValue');

        if(is_null($saveRegistrationStatusValue)) {
            $registrationType = is_null($code)? 'normal':$code;

            $event = $this->repository->getEvent(1);
            $user = Auth::user();

            $registration = $this->repository->getEventRegistration($event, $user);

            if(is_null($registration)) {

                $tickets = $this->repository->getEventTickets($event->id);

                return Response::html('@participant/encup/registration.html', [ 'links' => $this->links, 
                'user' => Auth::user(), 'tickets' => $tickets, 'registrationType' => $registrationType, 'event' => $event, 'exceptionalRegistration' => false
                ])->withoutCache();
            }

            return $this->checkEncupRegistrationStatus();
        } else {
            $saveRegistrationStatus = SystemStatus::tryFrom($saveRegistrationStatusValue);
            return Response::html('@participant/encup/registration.html', ['links' => $this->links, 'saveRegistrationStatus' => $saveRegistrationStatus])->withoutCache();
        }
    }

    public function saveEncupRegistration(Request $request): Response
    {
        $result = $this->service->saveEventRegistration($request);

        Session::set('saveRegistrationStatusValue', $result->status->value);
        
        return Response::redirect('/participante/encup/inscricao', 303);
    }

    public function checkEncupRegistrationStatus(): Response
    {
        Session::delete('savePaymentProofStatusValue');
        Session::delete('saveRegistrationStatusValue');

        $event = $this->repository->getEvent(1);
        $user = Auth::user();
        $registration = $this->repository->getEventRegistration($event, $user);

        if(is_null($registration)) {
            return Response::redirect('/participante/encup/inscricao', 303);
        }

        if($registration->status === EventRegistrationStatus::PENDING_PAYMENT) {
            return Response::redirect('/participante/encup/pagamento', 303);
        }

        return Response::redirect('/participante/encup/status', 303);
    }

    public function showEventPaymentPage(): Response
    {
        $savePaymentProofStatusValue = Session::get('savePaymentProofStatusValue');

        if(is_null($savePaymentProofStatusValue)) {
            $event = $this->repository->getEvent(1);
            $user = Auth::user();

            $registration = $this->repository->getEventRegistration($event, $user);

            if($registration?->status === EventRegistrationStatus::PENDING_PAYMENT) {
                $ticket = $this->repository->getEventTicket($registration->ticketId);
                $amountForPix = number_format((float) $registration->amountDue, 2, '.', '');
                
                $payload = new StaticPayload();
                $payload->setPixKey(Parser::KEY_TYPE_EMAIL, 'financeiro@brasilcursinhos.org')
                    ->setAmount($amountForPix)
                    ->setTid('ENCUP2026'.$ticket->id)
                    ->setDescription('Ingresso ' . strstr($ticket->name, '-', true) . $ticket->type->label())
                    ->setMerchantName('Brasil Cursinhos')
                    ->setMerchantCity('Sao Paulo');
                $pixCode = $payload->getPixCode();
                $pixQRCode = $payload->getQRCode();
                return Response::html('@participant/encup/payment.html', [ 'links' => $this->links, 
                    'user' => Auth::user(), 'ticket' => $ticket, 'registration' => $registration, 'event' => $event, 'pixCode' => $pixCode, 'pixQRCode' => $pixQRCode
                    ])->withoutCache();
            }

            return $this->checkEncupRegistrationStatus();
        } else {
            $savePaymentProofStatus = SystemStatus::tryFrom($savePaymentProofStatusValue);
            return Response::html('@participant/encup/payment.html', ['links' => $this->links, 'savePaymentProofStatus' => $savePaymentProofStatus])->withoutCache();
        }
    }

    public function savePaymentProof(Request $request): Response
    {
        $result = $this->service->savePaymentProof($request);

        Session::set('savePaymentProofStatusValue', $result->status->value);
        
        return Response::redirect('/participante/encup/pagamento', 303);
    }

    public function showEventStatusPage(): Response
    {
        $event = $this->repository->getEvent(1);
        $user = Auth::user();

        $registration = $this->repository->getEventRegistration($event, $user);

        if(is_null($registration) || $registration?->status === EventRegistrationStatus::PENDING_PAYMENT) {
            return $this->checkEncupRegistrationStatus();
        }

        $ticket = $this->repository->getEventTicket($registration->ticketId);
        
        return Response::html('@participant/encup/status.html', [ 'links' => $this->links, 
            'user' => Auth::user(), 'ticket' => $ticket, 'registration' => $registration, 'event' => $event
        ])->withoutCache();
    }
}
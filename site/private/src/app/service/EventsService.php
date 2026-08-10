<?php 
namespace App\Service;

use App\Enum\Role\UserRole;
use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Status\FinancialTransactionStatus;
use App\Enum\Status\SocialDiscountRequestStatus;
use App\Enum\Status\SystemStatus;
use App\Enum\Type\PaymentMethodType;
use App\Exception\FileException;
use App\Exception\ValidationException;
use App\Model\Result;
use App\Model\Event;
use App\Model\FinancialTransaction;
use App\Model\SocialDiscountRequest;
use App\Repository\AdministratorRepository;
use App\Repository\EventsRepository;
use App\Repository\FileRepository;
use App\Util\Auth;
use App\Util\FileManager;
use App\Validation\EventRegistrationValidator;
use Exception;
use PDOException;
use Router\Exception\RouteForbidden;
use Router\Request;

class EventsService
{
    private EventsRepository $repository;
    private EventRegistrationValidator $eventValidator;
    private FileRepository $fileRepository;

    public function __construct(
        EventsRepository $repository,
        EventRegistrationValidator $eventValidator,
        FileRepository $fileRepository
    )
    {
        $this->repository = $repository;
        $this->eventValidator = $eventValidator;
        $this->fileRepository = $fileRepository;
    }

    public function saveEventRegistration(Request $request): Result
    {
        try {
            $registration = $this->eventValidator->validateEncupRegistration($request->all());

            $user = Auth::user();

            if($user->id !== $registration->userId ) {
                if(Auth::hasRole(UserRole::ADMINSTRATOR)) {
                    $user = $this->repository->getUser($registration->userId);
                } else {
                    throw new RouteForbidden();
                }
            }

            if(is_null($user)) {
                throw new ValidationException(['user-id' => 'O usuário é inválido ou inexistente.']);
            }
            
            $event = $this->repository->getEvent($registration->eventId);

            if(is_null($event)) {
                throw new ValidationException(['event-id' => 'O evento é inválido ou inexistente.']);
            }

            $ticket = $this->repository->getEventTicket($registration->ticketId);

            if(is_null($ticket)) {
                throw new ValidationException(['ticket-id' => 'O ingresso é inválido ou inexistente.']);
            }
            
        } catch(ValidationException $exception) {
            return Result::failure(SystemStatus::VALIDATION_ERROR, $exception->getMessage(), $exception->getErrors());
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        if(!$event->canAcceptRegistrations()) {
            return Result::failure(SystemStatus::REGISTRATION_DATE_ERROR);
        }

        if(!is_null($this->repository->getEventRegistration($event, $user))) {
            return Result::failure(SystemStatus::DUPLICATED_REGISTRATION_ERROR);
        }

        if($user->getAge() < 18) {
            try {
                $userAAD = 'USER_ID_' . $user->id;
                $file = FileManager::saveUpload(
                    fileArray: $request->file('proof-authorization'),
                    encrypt: true,
                    encryptionAAD: $userAAD,
                    allowedMimeTypes: ['application/pdf'],
                    relativePath: 'encup/2026'
                );
                $fileId = $this->fileRepository->saveFile($file);
                $registration = $registration->withProofAuthorization($fileId);
            } catch(FileException $exception) {
                return Result::failure(SystemStatus::FILE_ERROR);
            } catch(PDOException $exception) {

            }
        }

        $registration = $registration->updatePrices($ticket->price, $ticket->price);
        if(bccomp('0.00', $ticket->price) === 0) {
            $registration = $registration->updateStatus(EventRegistrationStatus::CONFIRMED);
        }
        
        $attemptsInsert = 0;

        while($attemptsInsert < 5) {

            $registration = $registration->withRegistration($this->generateRegistration($event));
            
            try {

                $insertedRegistration = $this->repository->saveEventRegistration($registration);
            
                break;

            } catch(PDOException $exception) {

                $errorCode = $exception->errorInfo[1] ?? 0;
                $errorMessage = $exception->getMessage();
                $message = null;

                if ($errorCode === 1062) {
                    if(str_contains($errorMessage, 'idxEventRegistrationsRegistrationHash')) {
                        $attemptsInsert++;
                        continue;
                    }
                }

                return Result::failure(SystemStatus::DATABASE_ERROR, $message);

            } catch(Exception $exception) {

                return Result::failure(SystemStatus::DATABASE_ERROR);
            }
        }

        if(!isset($insertedRegistration) || is_null($insertedRegistration)) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        } else {
            $registration = $insertedRegistration;
        }

        //envia email de confirmação

        return Result::success();
    }

    private function generateRegistration(Event $event): string
    {
        return substr($event->year, -2) . '1' . $event->type->value . Auth::getRandomCode(4, true);
    }

    public function savePaymentProof(Request $request): Result
    {
        try {
            $userId = ValidatorService::validateInt($request->__get('user-id'));
            $eventId = ValidatorService::validateInt($request->__get('event-id'));
            
            if(!$userId) {
                throw new ValidationException(['user-id' => 'ID do usuário não informado.']);
            }

            if(!$eventId) {
                throw new ValidationException(['event-id' => 'ID do evento não informado.']);
            }

            $user = Auth::user();

            if($user->id !== $userId ) {
                if(Auth::hasRole(UserRole::ADMINSTRATOR)) {
                    $user = $this->repository->getUser($userId);
                } else {
                    throw new RouteForbidden();
                }
            }

            if(is_null($user)) {
                throw new ValidationException(['user-id' => 'O usuário é inválido ou inexistente.']);
            }
            
            $event = $this->repository->getEvent($eventId);

            if(is_null($event)) {
                throw new ValidationException(['event-id' => 'O evento é inválido ou inexistente.']);
            }

            $registration = $this->repository->getEventRegistration($event, $user);

            if(is_null($registration)) {
                throw new ValidationException(['registration' => 'Inscrição no evento não encontrata.']);
            }
            
        } catch(ValidationException $exception) {
            return Result::failure(SystemStatus::VALIDATION_ERROR, $exception->getMessage(), $exception->getErrors());
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        try {
            $userAAD = 'USER_ID_' . $user->id;
            $file = FileManager::saveUpload(
                fileArray: $request->file('payment-proof'),
                encrypt: true,
                encryptionAAD: $userAAD,
                allowedMimeTypes: ['application/pdf', 'image/*'],
                relativePath: 'encup/2026',
                returnContent: true
            );
            $pixIdExtractor = new PixIdExtractorService();
            $providerTransactionId = $pixIdExtractor->extractE2eId($file->content, $file->mimeType);
            $transaction = new FinancialTransaction(
                id: null,
                status: FinancialTransactionStatus::UNDER_REVIEW,
                paymentMethod: PaymentMethodType::PIX,
                totalAmount: $registration->amountDue,
                userId: $user->id,
                eventId: $event->id,
                registrationId: $registration?->id,
                idProofTransaction: null,
                providerTransactionId: $providerTransactionId
            );
            $beneficiaries = [];
            $beneficiaries[] = ['cpf' => $user->personalData->cpf, 'ticket-id' => $registration->ticketId];
            $transaction = $this->repository->saveFinancialTransaction($transaction, $file, $beneficiaries);
        } catch(FileException $exception) {
            return Result::failure(SystemStatus::FILE_ERROR);
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        // comprovante recebido

        return Result::success();
    }

    public function saveBatchPayment(Request $request, AdministratorRepository $adminRepo, int $amount): Result
    {
        try {
            $cpfUser = ValidatorService::validateCpf($request->__get('cpf-1'));
            $eventId = ValidatorService::validateInt($request->__get('event-id'));
            $totalAmount = ValidatorService::validateNumber($request->__get('total-amount'));
            
            if(!$cpfUser) {
                throw new ValidationException(['user-id' => 'ID do usuário não informado.']);
            }

            if(!$eventId) {
                throw new ValidationException(['event-id' => 'ID do evento não informado.']);
            }

            if(!$totalAmount) {
                throw new ValidationException(['total-amount' => 'Valor total não informado.']);
            }

            $user = $adminRepo->getUser($cpfUser);

            if(is_null($user)) {
                throw new ValidationException(['user-id' => 'O usuário é inválido ou inexistente.']);
            }
            
            $event = $this->repository->getEvent($eventId);

            if(is_null($event)) {
                throw new ValidationException(['event-id' => 'O evento é inválido ou inexistente.']);
            }
            $beneficiaries = [];
            for($i = 1; $i <= $amount; $i++) {
                $cpf = ValidatorService::validateCpf($request->__get('cpf-'.$i));
                $ticket = ValidatorService::validateInt($request->__get('ticket-id-'.$i));
                if(!$cpf || !$ticket) {
                    throw new ValidationException(['data' => 'CPF ou id do ingresso inválido.']);
                }
                $beneficiaries[] = ['cpf' => $cpf, 'ticket-id' => $ticket];
            }
            
        } catch(ValidationException $exception) {
            return Result::failure(SystemStatus::VALIDATION_ERROR, $exception->getMessage(), $exception->getErrors());
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        try {
            $userAAD = 'USER_ID_' . $user->id;
            $file = FileManager::saveUpload(
                fileArray: $request->file('payment-proof'),
                encrypt: true,
                encryptionAAD: $userAAD,
                allowedMimeTypes: ['application/pdf', 'image/*'],
                relativePath: 'encup/2026',
                returnContent: true
            );
            $pixIdExtractor = new PixIdExtractorService();
            $providerTransactionId = $pixIdExtractor->extractE2eId($file->content, $file->mimeType);
            $transaction = new FinancialTransaction(
                id: null,
                status: FinancialTransactionStatus::UNDER_REVIEW,
                paymentMethod: PaymentMethodType::PIX,
                totalAmount: $totalAmount,
                userId: $user->id,
                eventId: $event->id,
                registrationId: null,
                idProofTransaction: null,
                providerTransactionId: $providerTransactionId
            );
            $transaction = $this->repository->saveFinancialTransaction($transaction, $file, $beneficiaries);
        } catch(FileException $exception) {
            return Result::failure(SystemStatus::FILE_ERROR);
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        // comprovante recebido

        return Result::success();
    }

    public function saveSocialRequest(Request $request): Result
    {
        try {
            $userId = ValidatorService::validateInt($request->__get('user-id'));
            $eventId = ValidatorService::validateInt($request->__get('event-id'));
            
            if(!$userId) {
                throw new ValidationException(['user-id' => 'ID do usuário não informado.']);
            }

            if(!$eventId) {
                throw new ValidationException(['event-id' => 'ID do evento não informado.']);
            }

            $user = Auth::user();

            if($user->id !== $userId ) {
                if(Auth::hasRole(UserRole::ADMINSTRATOR)) {
                    $user = $this->repository->getUser($userId);
                } else {
                    throw new RouteForbidden();
                }
            }

            if(is_null($user)) {
                throw new ValidationException(['user-id' => 'O usuário é inválido ou inexistente.']);
            }
            
            $event = $this->repository->getEvent($eventId);

            if(is_null($event)) {
                throw new ValidationException(['event-id' => 'O evento é inválido ou inexistente.']);
            }

            $registration = $this->repository->getEventRegistration($event, $user);

            if(is_null($registration)) {
                throw new ValidationException(['registration' => 'Inscrição no evento não encontrata.']);
            }
            
        } catch(ValidationException $exception) {
            return Result::failure(SystemStatus::VALIDATION_ERROR, $exception->getMessage(), $exception->getErrors());
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        try {
            $userAAD = 'USER_ID_' . $user->id;
            $file = FileManager::saveUpload(
                fileArray: $request->file('payment-proof'),
                encrypt: true,
                encryptionAAD: $userAAD,
                allowedMimeTypes: ['application/pdf', 'image/*'],
                relativePath: 'encup/2026',
                returnContent: true
            );
            //$pixIdExtractor = new PixIdExtractorService();
            //$providerTransactionId = $pixIdExtractor->extractE2eId($file->content, $file->mimeType);
            $transaction = new SocialDiscountRequest(
                id: null,
                status: SocialDiscountRequestStatus::UNDER_REVIEW,
                ticketId: $registration->ticketId,
                registrationId: $registration->id,
                idProofRequest: null
            );
            $transaction = $this->repository->saveSocialRequest($transaction, $file);
        } catch(FileException $exception) {
            return Result::failure(SystemStatus::FILE_ERROR);
        } catch(PDOException $exception) {
            return Result::failure(SystemStatus::DATABASE_ERROR);
        }

        // comprovante recebido

        return Result::success();
    }

}
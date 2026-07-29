<?php 
namespace App\Repository;

use App\Enum\Modality\EventModality;
use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Status\EventStatus;
use App\Enum\Status\UserStatus;
use App\Enum\Type\EventRegistrationType;
use App\Enum\Type\EventTicketType;
use App\Enum\Type\EventType;
use App\Enum\Type\UserType;
use App\Model\EmergencyData;
use App\Model\Event;
use App\Model\EventRegistration;
use App\Model\EventTicket;
use App\Model\File;
use App\Model\FinancialTransaction;
use App\Model\PersonalData;
use App\Model\User;
use App\Util\Log;
use App\Util\Crypto;
use DateTimeImmutable;
use PDO;

class EventsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEventTickets(int $eventId): array
    {
        $stmt = $this->pdo->prepare("SELECT `idEventTicket` AS `id`, `name`, `price`, `paymentDetails`, `type`, `description`  FROM `EVENT_TICKETS` WHERE `idEvent` = :idEvent AND `isActive` = true");
        $stmt->bindValue('idEvent', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $tickets = [];

        foreach($result as $row) {
            $tickets[] = new EventTicket(
                id: $row->id,
                name: $row->name,
                price: $row->price,
                paymentDetails: json_decode($row->paymentDetails, true),
                type: EventTicketType::tryFrom($row->type),
                description: $row->description
            );
        }
        
        return $tickets;
    }

    public function getEventTicket(int $eventTicketId): ?EventTicket
    {
        $stmt = $this->pdo->prepare("SELECT `idEventTicket` AS `id`, `name`, `price`, `paymentDetails`, `type`, `description`, `isActive`  FROM `EVENT_TICKETS` WHERE `idEventTicket` = :idEventTicket LIMIT 1");
        $stmt->bindValue('idEventTicket', $eventTicketId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        $eventTicket = null;
        
        if($result) {
            $eventTicket = new EventTicket(
                id: $result->id,
                name: $result->name,
                price: $result->price,
                paymentDetails: json_decode($result->paymentDetails, true),
                type: EventTicketType::tryFrom($result->type),
                description: $result->description,
                isActive: (bool)$result->isActive
            );
        }
        
        return $eventTicket;
    }

    public function getEvent(int $eventId): ?Event
    {
        $stmt = $this->pdo->prepare("SELECT `idEvent` AS `id`, `title`, `edition`, `year`, `location`, `modality`, `type`, `status`, `descriptions`, `registrationOpenAt`, `registrationCloseAt`, `socialRequestOpenAt`, `socialRequestCloseAt` FROM `EVENTS` WHERE `idEvent` = :idEvent LIMIT 1");
        $stmt->bindValue('idEvent', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        $event = null;
        
        if($result) {
            $event = new Event(
                id: $result->id,
                title: $result->title,
                edition: $result->edition,
                year: $result->year,
                location: $result->location,
                modality: EventModality::tryFrom($result->modality),
                type: EventType::tryFrom($result->type),
                status: EventStatus::tryFrom($result->status),
                descriptions: is_null($result->descriptions)? []:json_decode($result->descriptions, true),
                registrationOpenAt: new DateTimeImmutable($result->registrationOpenAt),
                registrationCloseAt: new DateTimeImmutable($result->registrationCloseAt),
                socialRequestOpenAt: new DateTimeImmutable($result->socialRequestOpenAt),
                socialRequestCloseAt: new DateTimeImmutable($result->socialRequestCloseAt),
            );
        }
        
        return $event;
    }

    public function saveEventRegistration(EventRegistration $eventRegistration): EventRegistration
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('INSERT INTO `EVENT_REGISTRATIONS` ( `registration`, `registrationHash`, `type`, `status`, `basePrice`, `amountDue`, `organizationName`, `additionalData`, `emergencyData`, `idEvent`, `idEventTicket`, `idUser`, `idProofAuthorization`, `createdAt`, `updatedAt` ) VALUES ( :registration, :registrationHash, :type_, :status_, :basePrice, :amountDue, :organizationName, :additionalData, :emergencyData, :idEvent, :idEventTicket, :idUser, :idProofAuthorization, NOW(), NOW() )');
            $userAAD = 'USER_ID_' . $eventRegistration->userId;
            $stmt->bindValue(':registration', Crypto::encrypt($eventRegistration->registration, $userAAD), PDO::PARAM_LOB);
            $stmt->bindValue(':registrationHash', Crypto::hash($eventRegistration->registration), PDO::PARAM_LOB);
            $stmt->bindValue(':type_', $eventRegistration->type->value, PDO::PARAM_INT);
            $stmt->bindValue(':status_', $eventRegistration->status->value, PDO::PARAM_INT);
            $stmt->bindValue(':basePrice', $eventRegistration->basePrice, PDO::PARAM_STR);
            $stmt->bindValue(':amountDue', $eventRegistration->amountDue, PDO::PARAM_STR);
            $stmt->bindValue(':organizationName', $eventRegistration->organizationName, PDO::PARAM_STR);
            $stmt->bindValue(':additionalData', json_encode($eventRegistration->additionalData, JSON_FORCE_OBJECT), PDO::PARAM_STR);
            $stmt->bindValue(':emergencyData', Crypto::encrypt(json_encode($eventRegistration->emergencyData, JSON_FORCE_OBJECT), $userAAD), PDO::PARAM_LOB);
            $stmt->bindValue(':idEvent', $eventRegistration->eventId, PDO::PARAM_INT);
            $stmt->bindValue(':idEventTicket', $eventRegistration->ticketId, PDO::PARAM_INT);
            $stmt->bindValue(':idUser', $eventRegistration->userId, PDO::PARAM_INT);
            $stmt->bindValue(':idProofAuthorization', $eventRegistration->proofAuthorizationId, is_null($eventRegistration->proofAuthorizationId)? PDO::PARAM_NULL:PDO::PARAM_INT);

            $stmt->execute();

            $this->pdo->commit();

            return $eventRegistration->withId($this->pdo->lastInsertId());

        } catch (\Exception $exception) {

            $this->pdo->rollBack();

            Log::error('Erro ao salvar inscrição no evento.', 'database.log', $exception->getMessage());

            throw $exception;
        }
    }

    public function getUser(int $userId): ?User
    {
        $stmt = $this->pdo->prepare("SELECT u.`idUser` AS `id`, u.`type`, u.`status`, pd.`fullName`, pd.`useSocialName`, pd.`socialName`, pd.`nickname`, pd.`pronouns`, pd.`genderIdentity`, pd.`ethnicity`, pd.`cpf`, pd.`birthDate`, pd.`email`, pd.`phone` FROM `USERS` u INNER JOIN `PERSONAL_DATA` pd ON (u.`idUser` = pd.`idUser`) WHERE u.`idUser` = :idUser LIMIT 1");
        $stmt->bindValue(':idUser', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if(!$user) {
            return null;
        }

        $userAAD = 'USER_ID_' . $user->id;
        $personalData = new PersonalData(
            fullName: Crypto::decrypt($user->fullName, $userAAD),
            useSocialName: (bool) $user->useSocialName,
            socialName: ($user->useSocialName == true)? Crypto::decrypt($user->socialName, $userAAD):null,
            nickname: Crypto::decrypt($user->nickname, $userAAD),
            pronouns: json_decode(Crypto::decrypt($user->pronouns, $userAAD)),
            genderIdentity: Crypto::decrypt($user->genderIdentity, $userAAD),
            ethnicity: Crypto::decrypt($user->ethnicity, $userAAD),
            cpf: Crypto::decrypt($user->cpf, $userAAD),
            birthDate: new \DateTimeImmutable(Crypto::decrypt($user->birthDate, $userAAD)),
            email: Crypto::decrypt($user->email, $userAAD),
            phone: Crypto::decrypt($user->phone, $userAAD),
        );
        
        return new User(
            id: $user->id,
            type: UserType::tryFrom($user->type),
            status: UserStatus::tryFrom($user->status),
            roles: [],
            passwordHash: null,
            loginAttempts: null,
            personalData: $personalData
        );
    }

    public function getEventRegistration(Event $event, User $user): ?EventRegistration
    {
        $stmt = $this->pdo->prepare("SELECT `idEventRegistration` AS `id`, `registration`, `type`, `status`, `basePrice`, `amountDue`, `organizationName`, `additionalData`, `emergencyData`, `idEvent`, `idEventTicket`, `idUser`, `idProofAuthorization` FROM `EVENT_REGISTRATIONS` WHERE `idUser` = :idUser AND `idEvent` = :idEvent LIMIT 1");
        $stmt->bindValue(':idUser', $user->id, PDO::PARAM_INT);
        $stmt->bindValue(':idEvent', $event->id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        $registration = null;
        
        if($result) {

            $userAAD = 'USER_ID_' . $result->idUser;

            $emergencyDataJson = Crypto::decrypt($result->emergencyData, $userAAD);
            $emergencyDataArray = json_decode($emergencyDataJson, true);
            $emergencyData = EmergencyData::fromArray($emergencyDataArray);

            $registration = new EventRegistration(
                id: (int)$result->id,
                registration: Crypto::decrypt($result->registration, $userAAD),
                type: EventRegistrationType::tryFrom($result->type),
                status: EventRegistrationStatus::tryFrom($result->status),
                basePrice: $result->basePrice,
                amountDue: $result->amountDue,
                organizationName: $result->organizationName,
                additionalData: json_decode($result->additionalData, true),
                emergencyData: $emergencyData,
                eventId: (int)$result->idEvent,
                ticketId: (int)$result->idEventTicket,
                userId: (int)$result->idUser
            );
        }

        return $registration;
    }

    public function saveFinancialTransaction(FinancialTransaction $transaction, File $file): FinancialTransaction
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO `FILES` (`originalName`, `storedName`, `path`, `mimeType`, `size`, `isEncrypted`, `createdAt`) VALUES (:originalName, :storedName, :path_, :mimeType, :size_, :isEncrypted, NOW())");
            $stmt->bindValue(':originalName', $file->originalName, PDO::PARAM_STR);
            $stmt->bindValue(':storedName', $file->storedName, PDO::PARAM_STR);
            $stmt->bindValue(':path_', $file->path, PDO::PARAM_STR);
            $stmt->bindValue(':mimeType', $file->mimeType, PDO::PARAM_STR);
            $stmt->bindValue(':size_', $file->size, PDO::PARAM_INT);
            $stmt->bindValue(':isEncrypted', $file->isEncrypted, PDO::PARAM_BOOL);
            $stmt->execute();
            
            $transaction = $transaction->withProofTransaction((int)$this->pdo->lastInsertId());

            $stmt2 = $this->pdo->prepare('INSERT INTO `FINANCIAL_TRANSACTIONS` (`status`, `totalAmount`, `paymentMethod`, `providerTransactionId`, `providerTransactionIdHash`, `idProofTransaction`, `idUser`, `idEvent`, `idEventRegistration`, `createdAt`, `updatedAt` ) VALUES ( :status_, :totalAmount, :paymentMethod, :providerTransactionId, :providerTransactionIdHash, :idProofTransaction, :idUser, :idEvent, :idEventRegistration, NOW(), NOW())');
            
            $stmt2->bindValue(':status_', $transaction->status->value, PDO::PARAM_INT);
            $stmt2->bindValue(':totalAmount', $transaction->totalAmount, PDO::PARAM_STR);
            $stmt2->bindValue(':paymentMethod', $transaction->paymentMethod->value, PDO::PARAM_INT);
            
            if (!is_null($transaction->providerTransactionId)) {
                $userAAD = 'USER_ID_' . $transaction->userId;
                $stmt2->bindValue(':providerTransactionId', Crypto::encrypt($transaction->providerTransactionId, $userAAD), PDO::PARAM_LOB);
                $stmt2->bindValue(':providerTransactionIdHash', Crypto::hash($transaction->providerTransactionId), PDO::PARAM_LOB);
            } else {
                $stmt2->bindValue(':providerTransactionId', null, PDO::PARAM_NULL);
                $stmt2->bindValue(':providerTransactionIdHash', null, PDO::PARAM_NULL);
            }

            $stmt2->bindValue(':idProofTransaction', $transaction->idProofTransaction, PDO::PARAM_INT);
            $stmt2->bindValue(':idUser', $transaction->userId, PDO::PARAM_INT);
            $stmt2->bindValue(':idEvent', $transaction->eventId, PDO::PARAM_INT);
            $stmt2->bindValue(':idEventRegistration', $transaction->registrationId, is_null($transaction->registrationId) ? PDO::PARAM_NULL : PDO::PARAM_INT);

            $stmt2->execute();

            $transaction = $transaction->withId($this->pdo->lastInsertId());

            $stmt3 = $this->pdo->prepare('UPDATE `EVENT_REGISTRATIONS` SET `status` = :status_, `updatedAt` = NOW() WHERE `idEventRegistration` = :idEventRegistration LIMIT 1');
            $stmt3->bindValue(':status_', EventRegistrationStatus::PAYMENT_UNDER_REVIEW->value, PDO::PARAM_INT);
            $stmt3->bindValue(':idEventRegistration', $transaction->registrationId, PDO::PARAM_INT);

            $stmt3->execute();

            $this->pdo->commit();

            return $transaction;

        } catch (\Exception $exception) {

            $this->pdo->rollBack();

            Log::error('Erro ao salvar transação financeira.', 'database.log', $exception->getMessage());

            throw $exception;
        }
    }
}
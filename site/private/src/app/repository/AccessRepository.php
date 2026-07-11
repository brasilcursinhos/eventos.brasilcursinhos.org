<?php 
namespace App\Repository;

use App\Enum\Role\UserRole;
use App\Enum\Status\UserStatus;
use App\Enum\Type\UserType;
use App\Model\PersonalData;
use App\Model\User;
use App\Util\Crypto;
use App\Util\Log;
use PDO;

class AccessRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUser(string $cpf): Object | false
    {
        $stmt = $this->pdo->prepare("SELECT u.`idUser` AS `id`, pd.`email`, pd.`nickname`, u.`status` FROM `USERS` u INNER JOIN `PERSONAL_DATA` pd ON (u.`idUser` = pd.`idUser`) WHERE u.`cpfHash` = :cpfHash LIMIT 1");
        $stmt->bindValue(':cpfHash', Crypto::hash($cpf), PDO::PARAM_LOB);
        $stmt->execute();
        $result = $stmt->fetch();

        if($result) {
            $userADD = 'USER_ID_' . $result->id;
            $result->nickname = Crypto::decrypt($result->nickname, $userADD);
            $result->email = Crypto::decrypt($result->email, $userADD);
        }
        
        return $result;
    }

    public function insertVerificationCode(string $code, int $idUser)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `VERIFICATION_CODES` (`code`, `sentAt`, `idUser`) VALUES (:code, NOW(), :idUser)");
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->bindValue(':idUser', $idUser, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch(\Exception $excetion) {
            Log::error("Erro ao inserir código de verificação.", "database.log", $excetion->getMessage());
            return false;
        }
        
    }

    public function confirmVerificationCode(string $code) 
    {
        try {
            
            $stmt = $this->pdo->prepare("UPDATE `VERIFICATION_CODES` SET `confirmedAt` = NOW() WHERE `code` = :code AND `sentAt` >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $this->pdo->prepare("SELECT pd.`nickname`, pd.`cpf`, vc.`idUser` AS `id` FROM `VERIFICATION_CODES` vc INNER JOIN `PERSONAL_DATA` pd ON (vc.`idUser` = pd.`idUser`) WHERE `code` = :code LIMIT 1");
                $stmt->bindValue(':code', $code, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetch();

                if($result) {
                    $userADD = 'USER_ID_' . $result->id;
                    $result->nickname = Crypto::decrypt($result->nickname, $userADD);
                    $result->cpf = Crypto::decrypt($result->cpf, $userADD);
                }
                
                return $result;
            } else {
                return false;
            }
        } catch(\Exception $excetion) {
            Log::error("Erro ao confirmar código de verificação.", "database.log", $excetion->getMessage());
            return false;
        }
    }

    public function insertNewPasswordHash(string $code, string $hash)
    {
        try {

            $this->pdo->beginTransaction();

            $stmtSelect = $this->pdo->prepare('
                SELECT `idUser`
                FROM `VERIFICATION_CODES`
                WHERE `code` = :code 
                FOR UPDATE
            ');
            $stmtSelect->execute(['code' => $code]);
            $idUser = $stmtSelect->fetchColumn();

            if ($idUser === false) {
                $this->pdo->rollBack();
                return false; 
            }

            $stmtUpdate = $this->pdo->prepare('
                UPDATE `USERS`
                SET `passwordHash` = :passwordHash, `status` = :status, `updatedAt` = NOW()
                WHERE `idUser` = :idUser
            ');
            $stmtUpdate->execute([
                'passwordHash' => $hash,
                'status'       => UserStatus::ACTIVE->value,
                'idUser'       => $idUser
            ]);

            $stmtDelete = $this->pdo->prepare('
                DELETE FROM `VERIFICATION_CODES`
                WHERE `code` = :code
            ');
            $stmtDelete->execute(['code' => $code]);

            $this->pdo->commit();

            return true;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            Log::error('Erro ao atualizar senha.', 'database.log', $e->getMessage());
            
            return false;
        }
    }

    public function getUserLogin(string $cpf): ?User
    {
        $stmt = $this->pdo->prepare("SELECT u.`idUser` AS `id`, u.`passwordHash`, u.`type`, u.`status`, u.`loginAttempts`, pd.`fullName`, pd.`useSocialName`, pd.`socialName`, pd.`nickname`, pd.`pronouns`, pd.`genderIdentity`, pd.`ethnicity`, pd.`cpf`, pd.`birthDate`, pd.`email`, pd.`phone` FROM `USERS` u INNER JOIN `PERSONAL_DATA` pd ON (u.`idUser` = pd.`idUser`) WHERE `cpfHash` = :cpfHash LIMIT 1");
        $stmt->bindValue(':cpfHash', Crypto::hash($cpf), PDO::PARAM_LOB);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if($user) {
            
            $stmtRole = $this->pdo->prepare("SELECT `role` FROM `USER_ROLES` WHERE `idUser` = :idUser");
            $stmtRole->bindValue(':idUser', $user->id);
            $stmtRole->execute();
            $roles = $stmtRole->fetchAll();
            $userRoles = [];
            foreach($roles as $role) {
                $userRole = UserRole::tryFrom($role->role);
                if($userRole instanceof UserRole) {
                    $userRoles[] = $userRole;
                }
            }
        } else {
            return null;
        }

        $userAAD = 'USER_ID_' . $user->id;
        $personalData = new PersonalData(
            fullName: Crypto::decrypt($user->fullName, $userAAD),
            useSocialName: $user->useSocialName,
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
            roles: $userRoles,
            passwordHash: $user->passwordHash,
            loginAttempts: $user->loginAttempts,
            personalData: $personalData
        );
    }

    public function updatePasswordHash(User $user, string $newHash): bool
    {
        try {
            $update = $this->pdo->prepare("UPDATE `USERS` SET `passwordHash` = :passwordHash, `updatedAt` = NOW() WHERE `idUser` = :idUser");
            $update->bindValue(':newHash', $newHash, PDO::PARAM_STR);
            $update->bindValue(':idUser', $user->id, PDO::PARAM_INT);
            $update->execute();
            return true;
        } catch(\Exception $exception) {
            return false;
        }
    }

    public function updateLoginAttempts(User $user, int $loginAttempts): bool
    {
        try {
            $update = $this->pdo->prepare("UPDATE `USERS` SET `loginAttempts` = :loginAttempts, `updatedAt` = NOW() WHERE `idUser` = :idUser");
            $update->bindValue(':loginAttempts', $loginAttempts, PDO::PARAM_INT);
            $update->bindValue(':idUser', $user->id, PDO::PARAM_INT);
            $update->execute();
            return true;
        } catch(\Exception $exception) {
            return false;
        }
    }

    public function updateUserStatus(User $user, UserStatus $status): bool
    {
        try {
            $update = $this->pdo->prepare("UPDATE `USERS` SET `status` = :status_, `updatedAt` = NOW() WHERE `idUser` = :idUser");
            $update->bindValue(':status_', $status->value, PDO::PARAM_INT);
            $update->bindValue(':idUser', $user->id, PDO::PARAM_INT);
            $update->execute();
            return true;
        } catch(\Exception $exception) {
            return false;
        }
    }

    public function isRegistred(string $cpf): bool
    {
        return false;
    }

    public function saveRegistrationn(PersonalData $personalData): int
    {
        try {

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO `USERS` (`cpfHash`, `type`, `status`, `createdAt`, `updatedAt`) VALUES(:cpfHash, :type_, :status_, NOW(), NOW())");
            $stmt->bindValue(':cpfHash', Crypto::hash($personalData->cpf), PDO::PARAM_LOB);
            $stmt->bindValue(':type_', UserType::EVENT_PARTICIPANT->value, PDO::PARAM_INT);
            $stmt->bindValue(':status_', UserStatus::PENDING->value, PDO::PARAM_INT);
            $stmt->execute();


            $stmt = $this->pdo->prepare("INSERT INTO `PERSONAL_DATA` (`fullName`, `useSocialName`, `socialName`, `nickname`, `pronouns`, `genderIdentity`, `ethnicity`, `cpf`, `birthDate`, `email`, `emailHash`, `phone`, `phoneHash`, `address`, `idUser`, `createdAt`, `updatedAt`) VALUES(:fullName, :useSocialName, :socialName, :nickname, :pronouns, :genderIdentity, :ethnicity, :cpf, :birthDate, :email, :emailHash, :phone, :phoneHash, :address_, :idUser, NOW(), NOW())");

            $userId =  $this->pdo->lastInsertId();
            $userADD = 'USER_ID_' . $userId;
            $stmt->bindValue(':fullName', Crypto::encrypt($personalData->fullName, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':useSocialName', $personalData->useSocialName, PDO::PARAM_BOOL);
            if($personalData->useSocialName) {
                $stmt->bindValue(':socialName', Crypto::encrypt($personalData->socialName, $userADD), PDO::PARAM_LOB);
            } else {
                $stmt->bindValue(':socialName', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':nickname', Crypto::encrypt($personalData->nickname, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':pronouns', Crypto::encrypt(json_encode($personalData->pronouns), $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':genderIdentity', Crypto::encrypt($personalData->genderIdentity, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':ethnicity', Crypto::encrypt($personalData->ethnicity, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':cpf', Crypto::encrypt($personalData->cpf, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':birthDate', Crypto::encrypt($personalData->birthDate->format('Y-m-d'), $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':email', Crypto::encrypt($personalData->email, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':emailHash', Crypto::hash($personalData->email), PDO::PARAM_LOB);
            $stmt->bindValue(':phone', Crypto::encrypt($personalData->phone, $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':phoneHash', Crypto::hash($personalData->phone), PDO::PARAM_LOB);
            $stmt->bindValue(':address_', Crypto::encrypt(json_encode($personalData->address), $userADD), PDO::PARAM_LOB);
            $stmt->bindValue(':idUser', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("INSERT INTO `USER_ROLES` (`role`, `idUser`, `createdAt`) VALUES(:role_, :idUser, NOW())");
            $stmt->bindValue(':role_', UserRole::EVENT_PARTICIPANT->value, PDO::PARAM_INT);
            $stmt->bindValue(':idUser', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();

            return (int)$userId;

        } catch(\Exception $excetion) {

            $this->pdo->rollBack();

            Log::error('Erro ao inserir registro de usuário.', 'database.log', $excetion->getMessage());

            throw $excetion;
        }
    }

}
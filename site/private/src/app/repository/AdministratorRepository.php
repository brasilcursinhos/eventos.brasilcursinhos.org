<?php 
namespace App\Repository;

use App\Model\PersonalData;
use App\Util\Crypto;
use App\Model\User;
use App\Enum\Type\UserType;
use App\Enum\Status\UserStatus;
use PDO;

class AdministratorRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function updatePersonalData(int $userId, PersonalData $personalData) {
        
    }

    public function getUser(string $cpf): ?User 
    {
        $stmt = $this->pdo->prepare("SELECT u.`idUser` AS `id`, u.`type`, u.`status`, pd.`fullName`, pd.`useSocialName`, pd.`socialName`, pd.`nickname`, pd.`pronouns`, pd.`genderIdentity`, pd.`ethnicity`, pd.`cpf`, pd.`birthDate`, pd.`email`, pd.`phone` FROM `USERS` u INNER JOIN `PERSONAL_DATA` pd ON (u.`idUser` = pd.`idUser`) WHERE u.`cpfHash` = :cpfHash LIMIT 1");
        $stmt->bindValue(':cpfHash', Crypto::hash($cpf), PDO::PARAM_LOB);
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

    /**
     * @return User[]
     */
    public function getAllUsers(): array 
    {
        $stmt = $this->pdo->prepare("
            SELECT u.`idUser` AS `id`, u.`type`, u.`status`, pd.`fullName`, pd.`useSocialName`, 
                pd.`socialName`, pd.`nickname`, pd.`pronouns`, pd.`genderIdentity`, 
                pd.`ethnicity`, pd.`cpf`, pd.`birthDate`, pd.`email`, pd.`phone` 
            FROM `USERS` u 
            INNER JOIN `PERSONAL_DATA` pd ON (u.`idUser` = pd.`idUser`)
        ");
        
        $stmt->execute();
        
        $users = [];
        
        while ($user = $stmt->fetch()) {
            $userAAD = 'USER_ID_' . $user->id;
            
            $personalData = new PersonalData(
                fullName: Crypto::decrypt($user->fullName, $userAAD),
                useSocialName: (bool) $user->useSocialName,
                socialName: ($user->useSocialName == true) ? Crypto::decrypt($user->socialName, $userAAD) : null,
                nickname: Crypto::decrypt($user->nickname, $userAAD),
                pronouns: json_decode(Crypto::decrypt($user->pronouns, $userAAD)),
                genderIdentity: Crypto::decrypt($user->genderIdentity, $userAAD),
                ethnicity: Crypto::decrypt($user->ethnicity, $userAAD),
                cpf: Crypto::decrypt($user->cpf, $userAAD),
                birthDate: new \DateTimeImmutable(Crypto::decrypt($user->birthDate, $userAAD)),
                email: Crypto::decrypt($user->email, $userAAD),
                phone: Crypto::decrypt($user->phone, $userAAD),
            );
            
            $users[] = new User(
                id: $user->id,
                type: UserType::tryFrom($user->type),
                status: UserStatus::tryFrom($user->status),
                roles: [],
                passwordHash: null,
                loginAttempts: null,
                personalData: $personalData
            );
        }
        
        return $users;
    }

    public function updateUserCpf(string $cpf, int $userId): void
    {
       try {
        $this->pdo->beginTransaction();
        
        $userAAD = 'USER_ID_' . $userId;

        $stmtUsers = $this->pdo->prepare('UPDATE `USERS` SET `cpfHash` = :cpfHash, `updatedAt` = NOW() WHERE `idUser` = :idUser');
        $stmtUsers->bindValue(':cpfHash', Crypto::hash($cpf), PDO::PARAM_LOB);
        $stmtUsers->bindValue(':idUser', $userId, PDO::PARAM_INT);
        $stmtUsers->execute();

        $stmtPersonalData = $this->pdo->prepare('UPDATE `PERSONAL_DATA` SET `cpf` = :cpf, `updatedAt` = NOW() WHERE `idUser` = :idUser');
        $stmtPersonalData->bindValue(':cpf', Crypto::encrypt($cpf, $userAAD), PDO::PARAM_LOB);
        $stmtPersonalData->bindValue(':idUser', $userId, PDO::PARAM_INT);
        $stmtPersonalData->execute();

        $this->pdo->commit();

    } catch (\Exception $exception) {
        
        $this->pdo->rollBack();
        
        \App\Util\Log::error('Erro ao atualizar o CPF do usuário.', 'database.log', $exception->getMessage());
        
        throw $exception;
    }
    }

}

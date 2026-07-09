<?php 
namespace App\Repository;

use App\Model\PersonalData;
use App\Util\Crypto;
use Collator;
use PDO;

class AdministratorRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function updatePersonalData(int $userId, PersonalData $personalData) {
      
        try {

            $this->pdo->beginTransaction();

            $query = $this->pdo->prepare('UPDATE `PERSONAL_DATA` SET `firstName` = :firstName, `lastName` = :lastName, `nickname` = :nickname, `pronouns` = :pronouns, `gender` = :gender, `birthDate` = :birthDate, `updatedAt` = NOW() WHERE `idUser` = :idUser LIMIT 1');
            $userAAD = 'USER_ID_' . $userId;
            $query->bindValue(':idUser', $userId, PDO::PARAM_INT);
            $query->bindValue(':firstName', Crypto::encrypt($personalData->firstName, $userAAD), PDO::PARAM_LOB);
            $query->bindValue(':lastName', Crypto::encrypt($personalData->lastName, $userAAD), PDO::PARAM_LOB);
            $query->bindValue(':nickname', Crypto::encrypt($personalData->nickname, $userAAD), PDO::PARAM_LOB);
            $query->bindValue(':pronouns', Crypto::encrypt($personalData->pronouns, $userAAD), PDO::PARAM_LOB);
            $query->bindValue(':gender', Crypto::encrypt($personalData->gender, $userAAD), PDO::PARAM_LOB);
            $query->bindValue(':birthDate', Crypto::encrypt($personalData->birthDate->format('Y-m-d'), $userAAD), PDO::PARAM_LOB);

            $query->execute();

            $this->pdo->commit();

            return true;
        } catch (\Exception $exception) {
            
            $this->pdo->rollBack();
            
            $message = "Erro ao atualizar as informações de emergência do usuário. User id". $userId;
            \App\Util\Log::error($message, 'database.log', $exception->getMessage());
            
            return false;
        }
    }

    
}

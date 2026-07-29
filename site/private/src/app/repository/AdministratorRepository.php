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
      
        
    }

    
}

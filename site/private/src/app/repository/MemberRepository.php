<?php 
namespace App\Repository;

use App\Util\Crypto;
use App\Util\Log;
use App\Model\BcAccountTransaction;
use PDO;

class MemberRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insertBcAccountTransaction(BcAccountTransaction $transaction): BcAccountTransaction
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('INSERT IGNORE INTO `BC_ACCOUNT_TRANSACTIONS` (`transactionId`, `transactionIdHash`, `amount`, `datetime`, `status`, `createdAt`, `updatedAt`) VALUES ( :transactionId, :transactionIdHash, :amount, :datetime_, :status_, NOW(), NOW())');
            $stmt->bindValue(':transactionId', Crypto::encrypt($transaction->transactionId), PDO::PARAM_LOB);
            $stmt->bindValue(':transactionIdHash', Crypto::hash($transaction->transactionId), PDO::PARAM_LOB);
            $stmt->bindValue(':amount', $transaction->amount, PDO::PARAM_STR);
            $stmt->bindValue(':datetime_', $transaction->datetime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':status_', $transaction->status->value, PDO::PARAM_INT);

            $stmt->execute();

            $this->pdo->commit();

            return $transaction->withId($this->pdo->lastInsertId());

        } catch (\Exception $exception) {

            $this->pdo->rollBack();

            Log::error('MemberRespository | Erro ao salvar transação da conta BC.', 'database.log', $exception->getMessage());

            throw $exception;
        }
    }
}
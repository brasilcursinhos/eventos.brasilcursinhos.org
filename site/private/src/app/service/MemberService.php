<?php 
namespace App\Service;

use App\Enum\Status\BcAccountTransactionStatus;
use App\Enum\Status\SystemStatus;
use App\Model\BcAccountTransaction;
use App\Model\Result;
use App\Repository\MemberRepository;
use App\Util\FileManager;
use DateTimeImmutable;
use Exception;
use Router\Request;

class MemberService
{
    private MemberRepository $repository;

    public function __construct(
        MemberRepository $repository,
    )
    {
        $this->repository = $repository;
    }

    public function updateTransactions(Request $request): Result
    {
        try {
            $fileContent = FileManager::getUploadedContent($request->file('transactions-file'), ['text/csv'], 10485760);
        } catch(Exception $exception) {
            return Result::failure(SystemStatus::FILE_ERROR, $exception->getMessage());
        }

        $fileStream = fopen('php://memory', 'r+');
        fwrite($fileStream, $fileContent);
        rewind($fileStream);

        fgetcsv($fileStream, 1000, ',', '"', '\\');

        while (($row = fgetcsv($fileStream, 1000, ',', '"', '\\')) !== false) {

            if (count($row) < 7) {
                continue;
            }

            $paymentMethod = trim($row[5]);
            $transactionType = trim($row[6]);

            if($paymentMethod === 'Pix' && $transactionType === 'Aporte em conta') {

                $transactionId = trim($row[0]);
                $amount = trim($row[4]);
                $datetimeString = trim($row[3]);
                $datetime = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $datetimeString);

                $transaction = new BcAccountTransaction(
                    id: null,
                    transactionId: $transactionId,
                    amount: $amount,
                    datetime: $datetime,
                    status: BcAccountTransactionStatus::PENDING
                );

                $this->repository->insertBcAccountTransaction($transaction);
            }
        }

        return Result::success();
    }

}
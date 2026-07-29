<?php 
namespace App\Repository;

use App\Model\File;
use App\Util\Log;
use DateTimeImmutable;
use PDO;

class FileRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveFile(File $file): int|false
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `FILES` (`originalName`, `storedName`, `path`, `mimeType`, `size`, `isEncrypted`, `createdAt`) VALUES (:originalName, :storedName, :path_, :mimeType, :size_, :isEncrypted, NOW())");
            $stmt->bindValue(':originalName', $file->originalName, PDO::PARAM_STR);
            $stmt->bindValue(':storedName', $file->storedName, PDO::PARAM_STR);
            $stmt->bindValue(':path_', $file->path, PDO::PARAM_STR);
            $stmt->bindValue(':mimeType', $file->mimeType, PDO::PARAM_STR);
            $stmt->bindValue(':size_', $file->size, PDO::PARAM_INT);
            $stmt->bindValue(':isEncrypted', $file->isEncrypted, PDO::PARAM_BOOL);
            $stmt->execute();
            
            return (int) $this->pdo->lastInsertId();

        } catch(\Exception $exception) {

            $message = 'FileRepository | Erro ao inserir referência ao arquivo: ' .
                        $file->path . '/' . $file->storedName;
            Log::error($message, 'database.log', $exception->getMessage());

            return false;
        }
        
    }

    public function getFile(int $fileId): ?File
    {
        $stmt = $this->pdo->prepare("SELECT `idFile` AS `id`, `originalName`, `storedName`, `path`, `mimeType`, `size`, `isEncrypted`, `createdAt` FROM `FILES` WHERE `idFile` = :idFile LIMIT 1");
        $stmt->bindValue('idFile', $fileId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        $file = null;
        
        if($result) {
            $file = new File(
                id: $result->id,
                originalName: $result->originalName,
                storedName: $result->storedName,
                path: $result->path,
                mimeType: $result->mimeType,
                size: (int)$result->size,
                isEncrypted: (bool)$result->isEncrypted,
                createdAt: new DateTimeImmutable($result->createdAt)
            );
        }
        
        return $file;
    }
}
<?php 
namespace App\Repository;

use PDO;

class PublicPagesRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getVisibleLinks(): array
    {
        $stmt = $this->pdo->query("SELECT `label`, `url` FROM `LINKS` WHERE `isVisible` = true AND (`expiresAt` IS NULL OR `expiresAt` > NOW()) ORDER BY `order` ASC");
        
        return $stmt->fetchAll();
    }
}
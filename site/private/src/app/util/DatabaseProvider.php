<?php
// classe que gerencia a conexão com o banco de dados
namespace App\Util;

use PDO;
use Pdo\Mysql;
use PDOException;
use App\Util\Log;

class DatabaseProvider
{
    private static ?PDO $connection;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (!isset(self::$connection)){

            $type = $_ENV['DB_TYPE'] ?? 'mysql';
            $host = $_ENV['DB_HOST'];
            $port = $_ENV['DB_PORT'];
            $user = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASSWORD'];
            $database = $_ENV['DB_DATABASE'];

            $dsn = "$type:host=$host;port=$port;dbname=$database";

            $options = [
                Mysql::ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, 
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            try {
                
                self::$connection = new PDO($dsn, $user, $password, $options);

            } catch (PDOException $exception) {
                Log::error('Erro ao iniciar o banco de dados.', 'database.log', $exception->getMessage());
                throw $exception;
            }
        }

        return self::$connection;      
    }

}

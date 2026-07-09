<?php 
namespace App\Exception;

class DatabaseException extends \Exception
{
    private array $errors;

    public function __construct(array $errors = [], $message = 'Falha ao realizar operação no banco de dados.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
<?php 
namespace App\Exception;

class FileException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, $message = 'A manipulação do arquivo falhou.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
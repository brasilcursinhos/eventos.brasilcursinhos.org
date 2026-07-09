<?php 
namespace App\Exception;

class EmailException extends \Exception
{
    private array $errors;

    public function __construct(array $errors = [], $message = 'O envio do e-mail falhou.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
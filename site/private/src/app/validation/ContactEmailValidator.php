<?php 
namespace App\Validation;

use App\Service\ValidatorService;
use App\Exception\ValidationException;
use App\Model\ContactEmail;

class ContactEmailValidator
{
    private ValidatorService $validator;

    public function __construct(ValidatorService $validator)
    {
        $this->validator = $validator;
    }

    public function validate(array $data): ContactEmail
    {
        $errors = [];
        $validatedData = [];

        $name= $this->validator->validatePersonalName($data['name']);
        if($name !== false) {
            $validatedData['name'] = $name;
        } else {
            $errors['name'] = 'Nome ausente ou inválido!';
        }
        $senderEmail =  $this->validator->validateEmail($data['email']);
        if($senderEmail !== false) {
            $validatedData['sender'] = $senderEmail;
        } else {
            $errors['email'] = 'E-mail ausente ou inválido!';
        }
        switch($this->validator->validateInt($data['subject'])) {
            case 1:
                $validatedData['subject'] = 'Dúvida';
                $validatedData['recipient'] = 'contato@pes.ufsc.br';
                break;
            case 2:
                $validatedData['subject'] = 'Sugestão';
                $validatedData['recipient'] = 'contato@pes.ufsc.br';
                break;
            case 3:
                $validatedData['subject'] = 'Reclamação ou Elogio';
                $validatedData['recipient'] = 'contato@pes.ufsc.br';
                break;
            case 4:
                $validatedData['subject'] = 'Doação ou Parceria';
                $validatedData['recipient'] = 'financeiro@pes.ufsc.br';
                break;
            case 5:
                $validatedData['subject'] = 'Relatório de Dados Pessoais  (LGPD)';
                $validatedData['recipient'] = 'lgpd@pes.ufsc.br';
                break;
            case 6:
                $validatedData['subject'] = 'Exclusão de Dados Pessoais (LGPD)';
                $validatedData['recipient'] = 'lgpd@pes.ufsc.br';
                break;
            case 7:
                $validatedData['subject'] = 'Suporte Técnico (Site)';
                $validatedData['recipient'] = 'suporte@pes.ufsc.br';
                break;
            case 8:
                $validatedData['subject'] = 'Processo Seletivo ' . $this->validator->validateString($data['type-selection']);
                $validatedData['recipient'] = 'processoseletivo@pes.ufsc.br';
                $validatedData['registration'] = $this->validator->validateRegistrationNumber($data['registration-number']);
                break;
            default:
                $validatedData['subject'] = 'Outros Assuntos';
                $validatedData['recipient'] = 'contato@pes.ufsc.br';
        }
        $message = $this->validator->validateString($data['message']);
        if(!empty($message)) {
            $validatedData['message'] = $message;
            $validatedData['messageHtml'] = nl2br(htmlspecialchars($validatedData['message'], ENT_QUOTES));
        } else {
            $errors['message'] = 'Menssagem ausente ou inválida!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new ContactEmail(
            $validatedData['name'],
            $validatedData['sender'],
            $validatedData['subject'],
            $validatedData['recipient'],
            $validatedData['message'],
            $validatedData['messageHtml'],
            $validatedData['registration'] ?? ''
        );
    }
}
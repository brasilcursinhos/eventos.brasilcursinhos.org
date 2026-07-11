<?php

namespace App\Enum\Result;

enum AuthResult
{
    case SUCCESS;
    case INVALID_CREDENTIALS;
    case BLOCKED;
    case INACTIVE;

    public function message(): string
    {
        return match($this) {
            self::SUCCESS => 'Login realizado com sucesso.',
            self::INVALID_CREDENTIALS => 'Credenciais inválidas. Verifique seu usuário e senha.',
            self::BLOCKED => 'Sua conta foi bloqueada devido a múltiplas tentativas falhas. Utilize a recuperação de senha.',
            self::INACTIVE => 'Sua conta não está ativa para realizar o login.',
            default => 'Ocorreu um erro desconhecido ao realizar o login.'
        };
    }
}

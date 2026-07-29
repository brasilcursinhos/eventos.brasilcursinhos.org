<?php
namespace App\Enum\Status;

enum BcAccountTransactionStatus: int
{
    case PENDING = 1;
    case RECONCILED = 2;
    case FAILED = 3;
    case IGNORED = 4;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::RECONCILED => 'Conciliado',
            self::FAILED => 'Falha na conciliação',
            self::IGNORED => 'Ignorado'
        };
    }
}
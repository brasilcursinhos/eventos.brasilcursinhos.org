<?php 

namespace App\Enum\Status;

use App\Trait\EnumOptions;

enum UserStatus: int
{
    use EnumOptions;

    case PENDING = 1;
    case ACTIVE = 2;
    case LOCKED = 3;
    case DISABLED = 4;
    case SUSPENDED = 5;
    case BANNED = 6;
    case EXPIRED = 7;
    case ARCHIVED = 8;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Aguardando verificação',
            self::ACTIVE => 'Ativo',
            self::LOCKED => 'Bloqueado',
            self::DISABLED => 'Desligado',
            self::SUSPENDED => 'Suspenso',
            self::BANNED => 'Banido',
            self::EXPIRED => 'Expirado',
            self::ARCHIVED => 'Arquivado'
        };
    }

    public function isChangeablePassword(): bool
    {
        return match($this) {
            self::PENDING,
            self::ACTIVE,
            self::LOCKED => true,
            default => false,
        };
    }

}
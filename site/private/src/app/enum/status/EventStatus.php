<?php 

namespace App\Enum\Status;

use App\Trait\EnumOptions;

enum EventStatus: int
{
    use EnumOptions;

    case DRAFT = 1;
    case OPEN = 2;
    case IN_PROGRESS = 3;
    case SUSPENDED = 4;
    case CANCELLED = 5;
    case COMPLETED = 6;
    case ARCHIVED = 7;

    public function label(): string 
    {
        return match($this) {
            self::DRAFT => 'Rascunho (Não publicado)',
            self::OPEN => 'Inscrições Abertas',
            self::IN_PROGRESS => 'Em Andamento',
            self::SUSPENDED => 'Suspenso',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Encerrado',
            self::ARCHIVED => 'Arquivado (não visível)'
        };
    }

    public function isPubliclyVisible(): bool
    {
        return match($this) {
            self::DRAFT,
            self::ARCHIVED => false,
            default => true,
        };
    }

    public function allowsRegistration(): bool
    {
        return match($this) {
            self::OPEN => true,
            default => false,
        };
    }
}
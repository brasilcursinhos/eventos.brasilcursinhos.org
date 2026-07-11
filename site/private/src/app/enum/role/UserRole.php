<?php 

namespace App\Enum\Role;

use App\Trait\EnumOptions;

enum UserRole: int
{
    use EnumOptions;

    case ADMINSTRATOR = 1;
    case BC_MEMBER = 2;
    case EVENT_PARTICIPANT = 3;

    public function label(): string
    {
        return match($this) {
            self::ADMINSTRATOR => 'Administrador',
            self::BC_MEMBER => 'Membro BC',
            self::EVENT_PARTICIPANT => 'Participante'
        };
    }

    public static function members(): array
    {
        return [
            self::ADMINSTRATOR,
            self::BC_MEMBER
        ];
    }

}
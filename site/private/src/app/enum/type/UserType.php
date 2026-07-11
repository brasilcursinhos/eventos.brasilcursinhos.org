<?php 

namespace App\Enum\Type;

use App\Trait\EnumOptions;

enum UserType: int
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

    public function url(): string
    {
        return match($this) {
            self::ADMINSTRATOR => '/administrador',
            self::BC_MEMBER => '/membro',
            self::EVENT_PARTICIPANT => '/participante'
        };
    }

}
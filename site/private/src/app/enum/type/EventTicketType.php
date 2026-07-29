<?php 

namespace App\Enum\Type;

use App\Trait\EnumOptions;

enum EventTicketType: int
{
    use EnumOptions;

    case AFFILIATED_CUP_MEMBER = 1;
    case EXTERNAL_PARTICIPANT = 2;
    case BC_STAFF = 3;
    case CUP_STAFF = 4;
    case SPEAKER = 5;
    case GUEST = 6;
    
    public function label(): string
    {
        return match($this) {
            self::AFFILIATED_CUP_MEMBER => 'Membro de CUP filiado',
            self::EXTERNAL_PARTICIPANT => 'Participante externo',
            self::BC_STAFF => 'Staff BC',
            self::CUP_STAFF => 'Staff CUPs',
            self::SPEAKER => 'Palestrante',
            self::GUEST => 'Convidado'
        };
    }
}
<?php 

namespace App\Enum\Type;

use App\Trait\EnumOptions;

enum EventRegistrationType: int
{
    use EnumOptions;

    case AFFILIATED_CUP_MEMBER = 1;
    case UNAFFILIATED_CUP_MEMBER = 2;
    case EXTERNAL_PARTICIPANT = 3;
    case STAFF = 4;
    case SPEAKER = 5;
    case GUEST = 6;
    
    public function label(): string
    {
        return match($this) {
            self::AFFILIATED_CUP_MEMBER => 'Membro de CUP filiado',
            self::UNAFFILIATED_CUP_MEMBER => 'Membro de CUP não filiado',
            self::EXTERNAL_PARTICIPANT => 'Participante externo',
            self::STAFF => 'Staff',
            self::SPEAKER => 'Palestrante',
            self::GUEST => 'Convidado'
        };
    }
}
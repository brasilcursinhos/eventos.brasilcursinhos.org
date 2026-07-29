<?php 

namespace App\Enum\Modality;

use App\Trait\EnumOptions;

enum EventModality: int
{
    use EnumOptions;

    case PRESENCIAL = 1;
    case REMOTO = 2;
    case HIBRIDO = 3;

    public function label(): string 
    {
        return match($this) {
            self::PRESENCIAL => 'Presencial',
            self::REMOTO => 'Remoto',
            self::HIBRIDO => 'Híbrido'
        };
    }
}
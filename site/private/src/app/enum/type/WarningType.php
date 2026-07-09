<?php 

namespace App\Enum\Type;

use App\Trait\EnumOptions;

enum WarningType: int
{
    use EnumOptions;

    case EXCESSIVE_TALKING = 1;
    case IMPROPER_DEVICE_USAGE = 2;
    case LATE_ARRIVAL = 3;
    case LATE_AFTER_BREAK = 4;
    case EARLY_DEPARTURE = 5;
    case MISSING_ID_BADGE = 6;
    case SKIPPING_CLASS = 7;
    case OTHER = 8;

    public function label(): string 
    {
        return match($this) {
            self::EXCESSIVE_TALKING => 'Conversa excessiva durante a aula',
            self::IMPROPER_DEVICE_USAGE => 'Uso excessivo do celular durante a aula',
            self::LATE_ARRIVAL => 'Atraso no início da aula',
            self::LATE_AFTER_BREAK => 'Atraso no retorno do intervalo',
            self::EARLY_DEPARTURE => 'Saída adiantada',
            self::MISSING_ID_BADGE => 'Sem o crachá de identificação',
            self::SKIPPING_CLASS => 'Flagrado fora da sala durante a aula',
            self::OTHER => 'Outro motivo'
        };
    }
}
<?php 

namespace App\Enum\Status;

use App\Trait\EnumOptions;

enum SystemStatus: int
{
    use EnumOptions;

    case SUCCESS = 1;
    case EMAIL_ERROR = 2;
    case DATABASE_ERROR = 3;
    case VALIDATION_ERROR = 4;
    case SUBSCRIPTION_DATE_ERROR = 5;
    case DUPLICATED_SUBSCRIPTION_ERROR = 6;
    case UNKNOWN_ERROR = 7;

    public function label(): string
    {
        return match($this) {
            self::SUCCESS => 'Sucesso',
            self::EMAIL_ERROR => 'Erro no envio de E-mail',
            self::DATABASE_ERROR => 'Erro no Banco de Dados',
            self::VALIDATION_ERROR => 'Erro de validação de dados',
            self::SUBSCRIPTION_DATE_ERROR => 'Inscrição fora de prazo',
            self::DUPLICATED_SUBSCRIPTION_ERROR => 'Inscrição duplicada',
            self::UNKNOWN_ERROR => 'Erro desconhecido'
        };
    }

}
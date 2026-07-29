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
    case FILE_ERROR = 5;
    case REGISTRATION_DATE_ERROR = 6;
    case DUPLICATED_REGISTRATION_ERROR = 7;
    case UNKNOWN_ERROR = 8;

    public function label(): string
    {
        return match($this) {
            self::SUCCESS => 'Sucesso',
            self::EMAIL_ERROR => 'Erro no envio de e-mail',
            self::DATABASE_ERROR => 'Erro no banco de dados',
            self::VALIDATION_ERROR => 'Erro na validação de dados',
            self::FILE_ERROR => 'Erro na manipulação de arquivos',
            self::REGISTRATION_DATE_ERROR => 'Inscrição fora de prazo',
            self::DUPLICATED_REGISTRATION_ERROR => 'Inscrição duplicada',
            self::UNKNOWN_ERROR => 'Erro desconhecido'
        };
    }

}
<?php 

namespace App\Enum;

use App\Trait\EnumOptions;

enum UserType: int
{
    use EnumOptions;

    case ADMINSTRATOR = 1;
    case MEMBER = 2;
    case STUDENT = 3;
    case CANDIDATE = 4;
    

    public function label(): string
    {
        return match($this) {
            self::ADMINSTRATOR => 'Administrador',
            self::STUDENT => 'Aluno',
            self::MEMBER => 'Membro',
            self::CANDIDATE => 'Candidato'
        };
    }

    public function url(): string
    {
        return match($this) {
            self::ADMINSTRATOR => '/administrador',
            self::STUDENT => '/aluno',
            self::MEMBER => '/membro',
            self::CANDIDATE => '/candidato'
        };
    }

}
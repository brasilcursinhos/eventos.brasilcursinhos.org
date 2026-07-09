<?php 

namespace App\Enum;

use App\Trait\EnumOptions;

enum UserRole: int
{
    use EnumOptions;

    case ADMINSTRATOR = 1;
    case MANAGER = 2;
    case TEACHER = 3;
    case STUDENT = 4;
    case REVIEW_STUDENT = 5;
    case MANAGER_CANDIDATE = 6;
    case TEACHER_CANDIDATE = 7;
    case STUDENT_CANDIDATE = 8;
    case REVIEW_STUDENT_CANDIDATE = 9;
    case HR_MANAGER = 10;
    case ACADEMIC_MANAGER = 11;
    case PEDAGOGICAL_MANAGER = 12;
    case IT_MANAGER = 13;
    case MARKETING_MANAGER = 14;
    case FINANCE_MANAGER = 15;
    

    public function label(): string
    {
        return match($this) {
            self::ADMINSTRATOR => 'Administrador',
            self::MANAGER => 'Financeiro',
            self::TEACHER => 'Professor',
            self::STUDENT => 'Eventos',
            self::REVIEW_STUDENT => 'Aluno de Revisão',
            self::MANAGER_CANDIDATE => 'Candidato a Gestor',
            self::TEACHER_CANDIDATE => 'Candidato a Professor',
            self::STUDENT_CANDIDATE => 'Candidato a Aluno',
            self::REVIEW_STUDENT_CANDIDATE => 'Candidato a Aluno de Revisão',
            self::HR_MANAGER => 'Gestor - Gestão de Pessoas',
            self::ACADEMIC_MANAGER => ' Gestor - Ensino',
            self::PEDAGOGICAL_MANAGER => 'Gestor - Pedagógico',
            self::IT_MANAGER => 'Gestor - T.I.',
            self::MARKETING_MANAGER => 'Gestor - Marketing',
            self::FINANCE_MANAGER => 'Gestor - Financeiro'
        };
    }

    public static function candidates(): array
    {
        return [
            self::MANAGER_CANDIDATE,
            self::TEACHER_CANDIDATE,
            self::STUDENT_CANDIDATE,
            self::REVIEW_STUDENT_CANDIDATE
        ];
    }

    public static function members(): array
    {
        return [
            self::ADMINSTRATOR,
            self::MANAGER,
            self::TEACHER
        ];
    }

    public static function managers(): array
    {
        return [
            self::ADMINSTRATOR,
            self::MANAGER,
            self::HR_MANAGER,
            self::ACADEMIC_MANAGER,
            self::PEDAGOGICAL_MANAGER,
            self::IT_MANAGER,
            self::MARKETING_MANAGER,
            self::FINANCE_MANAGER
        ];
    }

}
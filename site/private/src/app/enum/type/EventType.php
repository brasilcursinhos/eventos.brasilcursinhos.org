<?php 

namespace App\Enum\Type;

use App\Trait\EnumOptions;

enum EventType: int
{
    use EnumOptions;

    case EDL = 1;
    case INOVA_CUP = 2;
    case ENCUP = 3;
    case EFOL = 4;

    public function label(): string 
    {
        return match($this) {
            self::EDL => 'EDL',
            self::INOVA_CUP => 'Inova CUP',
            self::ENCUP => 'ENCUP',
            self::EFOL => 'EFOL'
        };
    }

    public function title(): string 
    {
        return match($this) {
            self::EDL => 'Encontro de Lideranças',
            self::INOVA_CUP => 'Inova CUP',
            self::ENCUP => 'Encontro Nacional de Cursinhos Universitários Populares',
            self::EFOL => 'Encontro de Formação de Lideranças'
        };
    }

    public static function fromUrl(string $url): ?self
    {
        return match(strtolower($url)) {
            'edl' => self::EDL,
            'inova-cup' => self::INOVA_CUP,
            'encup' => self::ENCUP,
            'efol' => self::EFOL,
            default => null
        };
    }

}
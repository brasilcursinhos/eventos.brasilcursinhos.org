<?php 

namespace App\Enum\Status;

use App\Trait\EnumOptions;

enum EventRegistrationStatus: int
{
    use EnumOptions;

    case PENDING_PAYMENT = 1;
    case PAYMENT_UNDER_REVIEW = 2;
    case INVALID_PAYMENT = 3;
    case AWAITING_SOCIAL_ANALYSIS = 4;
    case CONFIRMED = 5;
    case CANCELED = 6;

    public function label(): string
    {
        return match($this) {
            self::PENDING_PAYMENT => 'Aguardando pagamento',
            self::PAYMENT_UNDER_REVIEW => 'Processando pagamento',
            self::INVALID_PAYMENT => 'Pagamento não confirmado',
            self::AWAITING_SOCIAL_ANALYSIS => 'Aguardando análise social',
            self::CONFIRMED => 'Inscrição confirmada',
            self::CANCELED => 'Inscrição cancelada'
        };
    }
}
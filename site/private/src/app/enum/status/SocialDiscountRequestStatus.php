<?php
namespace App\Enum\Status;

enum SocialDiscountRequestStatus: int
{
    case UNDER_REVIEW = 1;
    case APPROVED = 2;
    case REJECTED = 3;

    public function label(): string
    {
        return match($this) {
            self::UNDER_REVIEW => 'Em Análise',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado'
        };
    }
}
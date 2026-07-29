<?php
namespace App\Enum\Type;

enum PaymentMethodType: int
{
    case PIX = 1;
    case CREDIT_CARD = 2;
    case DEBIT_CARD = 3;
    case BOLETO = 4;
    case BANK_TRANSFER = 5;
    case CASH = 6;

    public function label(): string
    {
        return match($this) {
            self::PIX => 'Pix',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::DEBIT_CARD => 'Cartão de Débito',
            self::BOLETO => 'Boleto Bancário',
            self::BANK_TRANSFER => 'Transferência Bancária',
            self::CASH => 'Dinheiro',
        };
    }
}
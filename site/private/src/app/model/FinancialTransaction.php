<?php
namespace App\Model;

use App\Enum\Status\FinancialTransactionStatus;
use App\Enum\Type\PaymentMethodType;
use App\Trait\ModelImmutableCopy;

class FinancialTransaction
{
    use ModelImmutableCopy;

    public function __construct(
        public readonly ?int $id,
        public readonly FinancialTransactionStatus $status,
        public readonly string $totalAmount,
        public readonly PaymentMethodType $paymentMethod,
        public readonly ?int $idProofTransaction,
        public readonly int $userId,
        public readonly int $eventId,
        public readonly ?int $registrationId = null,
        public readonly ?string $justification = null,
        public readonly ?string $providerTransactionId = null,
    ) {
    }

    public function withId(int $id): self
    {
        return $this->copy(['id' => $id]);
    }

    public function withProofTransaction(int $idProofTransaction): self
    {
        return $this->copy([
            'idProofTransaction' => $idProofTransaction
        ]);
    }
}
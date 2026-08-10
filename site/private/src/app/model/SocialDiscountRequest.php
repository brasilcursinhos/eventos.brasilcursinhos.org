<?php
namespace App\Model;

use App\Enum\Status\FinancialTransactionStatus;
use App\Enum\Status\SocialDiscountRequestStatus;
use App\Trait\ModelImmutableCopy;

class SocialDiscountRequest
{
    use ModelImmutableCopy;

    public function __construct(
        public readonly ?int $id,
        public readonly SocialDiscountRequestStatus $status,
        public readonly ?int $idProofRequest,
        public readonly int $ticketId,
        public readonly int $registrationId,
        public readonly ?string $justification = null,
        public readonly ?string $grantedDiscountPercentage = null
    ) {
    }

    public function withId(int $id): self
    {
        return $this->copy(['id' => $id]);
    }

    public function withProofRequest(int $idProofRequest): self
    {
        return $this->copy([
            'idProofRequest' => $idProofRequest
        ]);
    }

    public function updateStatus(FinancialTransactionStatus $status): self
    {
        return $this->copy([
            'status' => $status
        ]);
    }
}
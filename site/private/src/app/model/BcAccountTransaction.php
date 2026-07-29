<?php
namespace App\Model;

use App\Enum\Status\BcAccountTransactionStatus;
use App\Trait\ModelImmutableCopy;
use DateTimeImmutable;

class BcAccountTransaction
{
    use ModelImmutableCopy;

    public function __construct(
        public readonly ?int $id,
        public readonly string $transactionId,
        public readonly string $amount,
        public readonly DateTimeImmutable $datetime,
        public readonly BcAccountTransactionStatus $status
    ) {
    }

    public function withId(int $id): self
    {
        return $this->copy(['id' => $id]);
    }
}
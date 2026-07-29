<?php 
namespace App\Model;

use App\Enum\Type\EventTicketType;

class EventTicket
{
    public function __construct(
        public readonly string $name,
        public readonly string $price,
        public readonly array $paymentDetails,
        public readonly EventTicketType $type,
        public readonly ?int $id = null,
        public readonly ?string $description = null,
        public readonly ?bool $isActive = true
    ) {

    }
}
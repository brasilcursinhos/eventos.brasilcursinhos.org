<?php 
namespace App\Model;

use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Type\EventRegistrationType;
use App\Trait\ModelImmutableCopy;

class EventRegistration
{
    use ModelImmutableCopy;

    public function __construct(
        public readonly ?int $id,
        public readonly ?string $registration,
        public readonly EventRegistrationType $type,
        public readonly EventRegistrationStatus $status,
        public readonly string $organizationName,
        public readonly array $additionalData,
        public readonly EmergencyData $emergencyData,
        public readonly int $eventId,
        public readonly int $ticketId,
        public readonly int $userId,
        public readonly string $basePrice = '0.00',
        public readonly string $amountDue = '0.00',
        public readonly ?int $proofAuthorizationId = null
    ) {

    }

    public function withRegistration(string $registration): self
    {
        return $this->copy(['registration' => $registration]);
    }

    public function withId(int $id): self
    {
        return $this->copy(['id' => $id]);
    }

    public function updatePrices(string $basePrice, string $amountDue): self
    {
        return $this->copy([
                'basePrice' => $basePrice,
                'amountDue' => $amountDue
            ]);
    }

    public function updateStatus(EventRegistrationStatus $status): self
    {
        return $this->copy([
                'status' => $status
            ]);
    }

    public function withProofAuthorization(int $id): self
    {
        return $this->copy(['proofAuthorizationId' => $id]);
    }
}
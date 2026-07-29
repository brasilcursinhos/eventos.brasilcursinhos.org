<?php 
namespace App\Model;

class EmergencyData
{
    public function __construct(
        public readonly string $name,
        public readonly string $kinship,
        public readonly string $phone,
        public readonly ?string $name2 = null,
        public readonly ?string $kinship2 = null,
        public readonly ?string $phone2 = null,
        public readonly ?string $healthConditions = null,
    ) { }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            kinship: $data['kinship'],
            phone: $data['phone'],
            name2: $data['name2'] ?? null,
            kinship2: $data['kinship2'] ?? null,
            phone2: $data['phone2'] ?? null,
            healthConditions: $data['healthConditions'] ?? null,
        );
    }
}
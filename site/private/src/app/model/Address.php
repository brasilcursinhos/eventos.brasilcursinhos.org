<?php 
namespace App\Model;

class Address
{
    public function __construct(
        public readonly string $cep,
        public readonly string $street,
        public readonly int $number,
        public readonly string $complement,
        public readonly string $neighborhood,
        public readonly string $city,
        public readonly string $state
    ) {

    }
}
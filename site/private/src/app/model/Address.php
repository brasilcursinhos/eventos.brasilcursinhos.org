<?php 
namespace App\Model;

class Address
{
    public function __construct(
        public readonly string $cep,
        public readonly string $city,
        public readonly string $state
    ) {

    }
}
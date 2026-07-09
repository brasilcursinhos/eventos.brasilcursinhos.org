<?php 
namespace App\Model;

class EmergencyInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $kinship,
        public readonly string $phone,
        public readonly ?string $healthConditions = null,
    ) { }
}
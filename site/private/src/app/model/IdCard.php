<?php 
namespace App\Model;

use DateTimeImmutable;

class IdCard
{
    public function __construct(
        public readonly string $number,
        public readonly string $issueDate,
        public readonly string $issuingAuthority,
        public readonly string $issuingState,
        public readonly string $motherName
    ) {

    }
}
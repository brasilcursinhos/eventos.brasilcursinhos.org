<?php 

namespace App\Model;

use DateTimeImmutable;

class PersonalData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $nickname,
        public readonly string $pronouns,
        public readonly string $gender,
        public readonly string $cpf,
        public readonly DateTimeImmutable $birthDate,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $socialName = null,
        public readonly ?IdCard $idCard = null,
        public readonly ?EmergencyInfo $emergencyInfo = null,
        public readonly ?Address $address = null,
        public readonly ?BankAccount $bankAccount = null,
    ) {}
}
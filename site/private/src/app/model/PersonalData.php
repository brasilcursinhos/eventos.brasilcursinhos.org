<?php 

namespace App\Model;

use DateTimeImmutable;

class PersonalData
{
    public function __construct(
        public readonly string $fullName,
        public readonly bool $useSocialName,
        public readonly string $nickname,
        public readonly array $pronouns,
        public readonly string $genderIdentity,
        public readonly string $ethnicity,
        public readonly string $cpf,
        public readonly DateTimeImmutable $birthDate,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $socialName = null,
        public readonly ?Address $address = null,
    ) {
        if (empty($this->pronouns)) {
            throw new \InvalidArgumentException('O array de pronomes não pode estar vazio.');
        }
    }
}
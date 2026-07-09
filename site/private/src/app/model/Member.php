<?php 

namespace App\Model;

use App\Enum\MemberType;
use App\Enum\MemberStatus;
use DateTimeImmutable;
use InvalidArgumentException;

class Member
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $registration,
        public readonly MemberType $type,
        public readonly MemberStatus $status,
        public readonly ?User $user = null,
        public readonly int $idUser,
        public readonly DateTimeImmutable $admissionDate,
        public readonly ?DateTimeImmutable $terminationDate = null,
    ) {
        if($this->user !== null && $this->user->id !== $this->idUser) {
            throw new InvalidArgumentException("Inconsistência detectada: O idUser ({$this->idUser}) difere do ID do objeto User ({$this->user->id}).");
        }
    }
}
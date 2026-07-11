<?php 

namespace App\Model;

use App\Enum\Type\UserType;
use App\Enum\Status\UserStatus;
use App\Enum\Role\UserRole;
use App\Trait\ModelImmutableCopy;

class User
{
    use ModelImmutableCopy;

    public function __construct(
        public readonly UserType $type,
        public readonly UserStatus $status,
        public readonly array $roles,
        public readonly ?int $id = null,
        public readonly ?string $username = null,
        public readonly ?string $passwordHash = null,
        public readonly ?int $loginAttempts = null,
        public readonly ?PersonalData $personalData = null
    ) {
        foreach($this->roles as $role) {
            if(!($role instanceof UserRole)) {
                throw new \InvalidArgumentException('O array de roles deve conter apenas instâncias do tipo UserRole.');
            }
        }
    }

    public function withId(int $id): self
    {
        return $this->copy(['id' => $id]);
    }

    public function getNickname(): ?string
    {
        return $this->personalData?->nickname;
    }
}
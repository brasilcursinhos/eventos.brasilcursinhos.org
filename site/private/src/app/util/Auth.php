<?php 
// classe responsável por gerenciar e controlar a autenticação do usuário no sistema
namespace App\Util;

use App\Enum\AuthResult;
use App\Model\User;
use App\Enum\UserType;
use App\Enum\UserStatus;
use App\Enum\UserRole;
use App\Model\PersonalData;
use App\Repository\AccessRepository;

class Auth
{

    private static ?User $user = null;

    public static function user(): ?User
    {
        if(!is_null(self::$user)) {
            return self::$user;
        }

        $sessionPayload = $_SESSION['AUTH_USER'] ?? null;

        if($sessionPayload !== null) {
            self::$user = self::hydrateUserFromSession($sessionPayload);
        }

        return self::$user;
    }

    public static function login(string $cpf, string $password, AccessRepository $repository): AuthResult
    {
        $user = $repository->getUserLogin($cpf);

        if(!$user) {
            return AuthResult::INVALID_CREDENTIALS;
        }

        if($user->status === UserStatus::LOCKED) {
            return AuthResult::BLOCKED;
        }

        if($user->status !== UserStatus::ACTIVE) {
            return AuthResult::INACTIVE;
        }

        if(password_verify($password, $user->passwordHash)) {

            if(password_needs_rehash($user->passwordHash, self::passwordHashAlgo(), self::passwordHashOptions())) {
                $newHash = self::passwordHash($password);
                $repository->updatePasswordHash($user, $newHash);
            }

            session_regenerate_id(true);

            CsrfToken::regenerate();

            $_SESSION['AUTH_USER'] = self::serializeUserForSession($user);

            if($user->loginAttempts !== 0) {
                $repository->updateLoginAttempts($user, 0);
            }

            self::$user = $user;

            return AuthResult::SUCCESS;

        } else {

            $repository->updateLoginAttempts($user, $user->loginAttempts + 1);
            
            if($user->loginAttempts + 1 >= 5) {
                $repository->updateUserStatus($user, UserStatus::LOCKED);
            }

            return AuthResult::INVALID_CREDENTIALS;
        }
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
        self::$user = null;
        session_start();
        session_regenerate_id(true);
        CsrfToken::regenerate();
    }

    private static function hydrateUserFromSession(array $sessionPayload): ?User
    {
        if (!isset($sessionPayload['id'], $sessionPayload['type'], $sessionPayload['status'], $sessionPayload['roles'])) {
            return null;
        }

        $type = UserType::tryFrom($sessionPayload['type']);
        $status = UserStatus::tryFrom($sessionPayload['status']);

        if ($type === null || $status === null) {
            return null;
        }

        $roles = array_values(array_filter(
            array_map(fn(mixed $roleId) => UserRole::tryFrom((int) $roleId), $sessionPayload['roles'])
        ));

        $personalData = null;
        if (isset($sessionPayload['personalData'])) {
            $pd = $sessionPayload['personalData'];
            
            if (isset($pd['firstName'], $pd['lastName'], $pd['nickname'], $pd['pronouns'], $pd['gender'], $pd['cpf'], $pd['birthDate'], $pd['email'], $pd['phone'])) {
                try {
                    $personalData = new PersonalData(
                        firstName: $pd['firstName'],
                        lastName: $pd['lastName'],
                        nickname: $pd['nickname'],
                        pronouns: $pd['pronouns'],
                        gender: $pd['gender'],
                        cpf: $pd['cpf'],
                        birthDate: new \DateTimeImmutable($pd['birthDate']),
                        email: $pd['email'],
                        phone: $pd['phone'],
                        socialName: $pd['socialName'] ?? null
                    );
                } catch (\Exception $e) {
                    $personalData = null; 
                }
            }
        }

        return new User(
            type: $type,
            status: $status,
            roles: $roles,
            id: (int) $sessionPayload['id'],
            personalData: $personalData
        );
    }

    private static function serializeUserForSession(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'type' => $user->type->value,
            'status' => $user->status->value,
            'roles' => array_map(fn(UserRole $role) => $role->value, $user->roles),
        ];

        if ($user->personalData !== null) {
            $payload['personalData'] = [
                'firstName' => $user->personalData->firstName,
                'lastName' => $user->personalData->lastName,
                'nickname' => $user->personalData->nickname,
                'pronouns' => $user->personalData->pronouns,
                'gender' => $user->personalData->gender,
                'cpf' => $user->personalData->cpf,
                'birthDate' => $user->personalData->birthDate->format('Y-m-d'),
                'email' => $user->personalData->email,
                'phone' => $user->personalData->phone,
                'socialName' => $user->personalData->socialName,
            ];
        }

        return $payload;
    }


    public static function isLoggedIn(): bool
    {
        $user = self::user();
        return $user !== null && $user->status === UserStatus::ACTIVE;
    }

    public static function hasRole(UserRole|array $role): bool
    {
        $user = self::user();

        if (is_null($user)) return false;

        if (is_array($role)) {
            return !empty(array_uintersect($user->roles, $role, fn($a, $b) => $a->name <=> $b->name));
        }

        return in_array($role, $user->roles, true);
    }

    public static function getRandomCode(int $size, bool $onlyNumbers = false): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        
        $limit = $onlyNumbers? 9:(strlen($characters)-1);
        
        $randomCode = '';
        
        for($i = 0; $i < $size; $i = $i+1){
           $randomCode .= $characters[random_int(0, $limit)];
        }
        
        return $randomCode;
    }

    public static function passwordHash(string $password): string
    {
        return password_hash($password, self::passwordHashAlgo(), self::passwordHashOptions());
    }

    private static function passwordHashOptions(): array
    {
        return [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
        ];
    }

    private static function passwordHashAlgo(): string
    {
        return PASSWORD_ARGON2ID;
    }
}





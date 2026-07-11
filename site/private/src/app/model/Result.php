<?php 
namespace App\Model;

use App\Enum\Status\SystemStatus;

class Result
{

    private function __construct(
        public readonly bool $isSuccess,
        public readonly SystemStatus $status,
        public readonly ?string $message = null,
        public readonly array $errors = [],
        public readonly mixed $data = null
    )
    { }

    public static function success(?string $message = null, mixed $data = null): self
    {
        return new self(
            isSuccess: true,
            status: SystemStatus::SUCCESS, 
            message: $message,
            data: $data
        );
    }

    public static function successWithStatus(SystemStatus $status, ?string $message = null, mixed $data = null): self
    {
        return new self(
            isSuccess: true,
            status: $status, 
            message: $message,
            data: $data
        );
    }

    public static function failure(SystemStatus $status, ?string $message = null, array $errors = []): self
    {
        return new self(
            isSuccess: false,
            status: $status,
            message: $message, 
            errors: $errors
        );
    }

}
<?php

namespace App\Model;

use DateTimeImmutable;

class File
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $storedName,
        public readonly string $path,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly bool $isEncrypted = false,
        public readonly ?int $id = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?string $content = null
    ) {
    }
}

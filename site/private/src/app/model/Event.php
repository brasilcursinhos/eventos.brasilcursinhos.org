<?php 

namespace App\Model;

use App\Enum\Type\EventType;
use App\Enum\Status\EventStatus;
use App\Enum\Modality\EventModality;
use DateTimeImmutable;

class Event
{
    public function __construct(
        public readonly string $title,
        public readonly int $edition,
        public readonly string $year,
        public readonly string $location,
        public readonly EventModality $modality,
        public readonly EventType $type,
        public readonly EventStatus $status,
        public readonly DateTimeImmutable $registrationOpenAt,
        public readonly DateTimeImmutable $registrationCloseAt,
        public readonly DateTimeImmutable $socialRequestOpenAt,
        public readonly DateTimeImmutable $socialRequestCloseAt,
        public readonly ?int $id = null,
        public readonly array $descriptions = [],
    ) {}

    public function isRegistrationOpen(\DateTimeInterface $now): bool
    {
        return $this->status->allowsRegistration() 
            && $now >= $this->registrationOpenAt 
            && $now <= $this->registrationCloseAt;
    }

    public function isSocialRequestOpen(\DateTimeInterface $now): bool
    {
        return $this->status->allowsRegistration() 
            && $now >= $this->socialRequestOpenAt 
            && $now <= $this->socialRequestCloseAt;
    }

    public function isPublished(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    public function canAcceptRegistrations(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->isRegistrationOpen($now);
    }

    public function canAcceptSocialRequests(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->isSocialRequestOpen($now);
    }
}
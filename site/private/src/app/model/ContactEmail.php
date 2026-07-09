<?php 
namespace App\Model;

class ContactEmail
{
    public function __construct(
        public readonly string $name,
        public readonly string $sender,
        public readonly string $subject,
        public readonly string $recipient,
        public readonly string $message,
        public readonly string $messageHtml,
        public readonly ?string $registration = null
    ) { }
}
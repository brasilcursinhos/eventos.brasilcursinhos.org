<?php 
namespace App\Model;

class PersonalContact
{
    public function __construct(
        public readonly string $email,
        public readonly string $phone
    ) {

    }
}
<?php
namespace App\DTOs\Auth;

class RegisterDTO
{
    public function __construct(
        public readonly ?string $name = "",
        public readonly string $email,
        public readonly string $password
    )
    {
    }
}
<?php

namespace App\DTOs\User;

class UserFilterDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly int $perPage = 15
    ) {}
}
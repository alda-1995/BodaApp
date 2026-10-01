<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class ForgotPasswordDTO
{
    public string $email;
    public function __construct(array $data)
    {
        $this->email = $data['email'];
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->only(['email']));
    }
    public function toArray(): array
    {
        return [
            'email' => $this->email
        ];
    }
}
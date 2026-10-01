<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class ResetPasswordDTO
{
    public string $token;
    public string $email;
    public string $password;
    public string $password_confirmation;

    public function __construct(array $data)
    {
        $this->token = $data['token'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->password_confirmation = $data['password_confirmation'];
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->only(['token', 'email', 'password', 'password_confirmation']));
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ];
    }
}
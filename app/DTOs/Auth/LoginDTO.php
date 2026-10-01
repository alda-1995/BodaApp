<?php
namespace App\DTOs\Auth;

class LoginDTO
{
    public string $email;
    public string $password;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self($request->only(['email', 'password']));
    }
}
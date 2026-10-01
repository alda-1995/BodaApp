<?php
namespace App\DTOs\Auth;

class UpdateUserDTO
{
   public string $name;
    public string $email;
    public ?string $password;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->password = $data['password'] ?? null;
    }

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self($request->only(['email', 'password', 'name']));
    }
}
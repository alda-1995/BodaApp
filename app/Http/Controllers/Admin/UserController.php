<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\User\UserFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserFilterRequest;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(protected UserService $userService, protected AuthService $authService)
    {
    }

    public function index(UserFilterRequest $request): View
    {
        $data = $request->validated();

        $dto = new UserFilterDTO(
            search: $data['search'] ?? null,
            status: $data['status'] ?? null,
            perPage: (int) ($data['per_page'] ?? 15)
        );

        $users = $this->userService->getPaginatedUsers($dto);

        return view('admin.users.index', compact('users'));
    }
}
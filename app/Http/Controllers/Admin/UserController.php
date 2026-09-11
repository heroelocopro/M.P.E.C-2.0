<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $role = isset($validated['role'])
            ? UserRole::from($validated['role'])
            : null;
        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->select(['id', 'name', 'email', 'personal_email', 'role', 'email_verified_at', 'created_at'])
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($search !== '', fn ($query) => $query->where(
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'personal_email' => $user->personal_email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        $countsByRole = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'role' => $role?->value,
                'search' => $search,
            ],
            'counts' => [
                'all' => $countsByRole->sum(),
                'administrators' => (int) $countsByRole->get(UserRole::Administrator->value, 0),
                'teachers' => (int) $countsByRole->get(UserRole::Teacher->value, 0),
                'students' => (int) $countsByRole->get(UserRole::Student->value, 0),
                'guardians' => (int) $countsByRole->get(UserRole::Guardian->value, 0),
            ],
        ]);
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Contraseña actualizada para {$user->name}."]);

        return back();
    }
}

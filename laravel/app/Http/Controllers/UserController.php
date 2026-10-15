<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Tenancy\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Owners add staff logins, set their roles, reset passwords and switch accounts off.
 * Users are deactivated rather than deleted so their history in the activity log keeps a name.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderByRaw("FIELD(role, 'owner', 'manager', 'staff')")->orderBy('username')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(Request $request, PlanLimits $limits): RedirectResponse
    {
        $data = $this->validated($request);
        $limits->ensureAllows('users', 'username');
        $user = User::create($data);

        return redirect()->route('users.index')->with('success', "User “{$user->username}” added.");
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user, PlanLimits $limits): RedirectResponse
    {
        $data = $this->validated($request, $user);

        // Switching an account back on counts towards the plan's user limit like a new one
        if (! $user->is_active && $data['is_active']) {
            $limits->ensureAllows('users', 'is_active');
        }

        $losesOwnerAccess = $user->isOwner() && $user->is_active && ($data['role'] !== 'owner' || ! $data['is_active']);
        if ($losesOwnerAccess && User::where('role', 'owner')->where('is_active', true)->count() === 1) {
            throw ValidationException::withMessages(['role' => 'There must always be at least one active owner.']);
        }
        if ($user->is($request->user()) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'You cannot switch off your own account.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('info', "User “{$user->username}” updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user?->id)],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'username.unique' => 'That username is already taken.',
        ]);

        return $data + ['is_active' => $user ? $request->boolean('is_active') : true];
    }
}

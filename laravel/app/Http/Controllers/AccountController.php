<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The signed-in user's own name, email and password.
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'current_password.required_with' => 'Enter your current password to set a new one.',
        ]);

        $user->fill(['name' => $data['name'] ?? null, 'email' => $data['email'] ?? null]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()->route('account.edit')->with('success', empty($data['password']) ? 'Your details were saved.' : 'Your details and password were saved.');
    }
}

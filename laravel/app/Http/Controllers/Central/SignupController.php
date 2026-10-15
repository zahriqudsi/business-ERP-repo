<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\User;
use App\Services\Settings;
use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * A new business signs up: its own database, an owner login and its name, then off to its own address.
 */
class SignupController extends Controller
{
    public function create(): View
    {
        return view('central.signup', ['baseDomain' => config('tenancy.base_domain')]);
    }

    public function store(Request $request, TenantProvisioner $provisioner, TenantManager $tenancy, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:63'],
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'username.regex' => 'The username may only use letters, numbers, dots, dashes and underscores.',
        ]);
        $data['slug'] = Str::lower(trim($data['slug']));

        try {
            $tenant = $provisioner->create(trim($data['business_name']), $data['slug'], $data['email']);
        } catch (ValidationException $e) {
            // The provisioner checks the subdomain (format, reserved, taken) and the name
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(
                fn (array $messages, string $field) => [$field === 'name' ? 'business_name' : $field => $messages],
            )->all());
        }

        // New sign-ups start their trial on the configured plan, if it exists
        if ($signupPlan = Plan::where('code', config('tenancy.billing.signup_plan'))->where('is_active', true)->first()) {
            $tenant->update(['plan_id' => $signupPlan->id]);
        }

        $tenancy->run($tenant, function () use ($data, $settings) {
            User::create([
                'username' => $data['username'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);
            $settings->save(['company.name' => trim($data['business_name']), 'company.email' => $data['email']]);
        });

        PlatformActivity::record('signed_up', "{$tenant->name} signed up as {$tenant->slug}", $tenant, data: ['owner_email' => $data['email']]);

        try {
            Mail::to($data['email'], $data['name'])->send(new WelcomeMail($tenant->name, $tenant->url('login'), $data['username']));
        } catch (Throwable $e) {
            // The business exists and works; a missing welcome email must not undo the sign-up
            Log::error("Could not send the welcome email for {$tenant->slug}: ".$e->getMessage());
        }

        return redirect()->away($tenant->url('login'));
    }
}

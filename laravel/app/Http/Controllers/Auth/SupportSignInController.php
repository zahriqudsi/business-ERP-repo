<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Landlord\ImpersonationToken;
use App\Models\Landlord\PlatformActivity;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Platform staff arrive here from the admin area with a one-time link, and are signed in as the
 * business's owner. Both the business's activity log and the platform log record it.
 */
class SupportSignInController extends Controller
{
    public function __invoke(Request $request, string $token, TenantManager $tenancy): RedirectResponse
    {
        $tenant = $tenancy->current();
        $admin = $tenant ? ImpersonationToken::redeem($token, $tenant) : null;
        abort_unless($admin, 404);

        $owner = User::where('role', 'owner')->where('is_active', true)->orderBy('id')->first();
        abort_unless($owner, 404, 'This business has no active owner to sign in as.');

        Auth::login($owner);
        $request->session()->regenerate();

        ActivityLog::record('signed_in', "Support ({$admin->name}) signed in as {$owner->display_name}", 'user', $owner->id);
        PlatformActivity::record('support_sign_in', "{$admin->name} signed in to {$tenant->name} as {$owner->username}", $tenant, $admin);

        return redirect()->route('dashboard');
    }
}

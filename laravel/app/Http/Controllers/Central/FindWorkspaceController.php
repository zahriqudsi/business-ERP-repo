<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\FindWorkspaceMail;
use App\Models\Landlord\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * "I forgot my business's address": the addresses are emailed to the owner, never shown on the page,
 * so the form can't be used to find out which businesses an email address owns.
 */
class FindWorkspaceController extends Controller
{
    public function create(): View
    {
        return view('central.find');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        $workspaces = Tenant::where('owner_email', $email)
            ->whereIn('status', Tenant::USABLE_STATUSES)
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant) => ['name' => $tenant->name, 'url' => $tenant->url('login')])
            ->all();

        if ($workspaces !== []) {
            Mail::to($email)->send(new FindWorkspaceMail($workspaces));
        }

        return back()->with('success', 'If that email address owns any businesses, we have sent their addresses to it.');
    }
}

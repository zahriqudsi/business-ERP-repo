<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\Settings;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The owner's Settings page: business details, logo, bank account, document defaults, tax and more.
 */
class SettingsController extends Controller
{
    /**
     * Uploaded logos live here, in the business's own storage, and are served by the logo route.
     */
    private const LOGO_DIR = 'branding';

    /**
     * Where logos were uploaded before tenancy (inside public/); they still display and can be replaced.
     */
    private const LEGACY_LOGO_DIR = 'uploads';

    public function __construct(private TenantManager $tenancy) {}

    public function edit(): View
    {
        return view('settings.edit', ['settings' => config('sjpos')]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'company.name' => ['required', 'string', 'max:100'],
            'company.tagline' => ['nullable', 'string', 'max:150'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.website' => ['nullable', 'string', 'max:255'],
            'company.job_phone' => ['nullable', 'string', 'max:50'],
            'company.job_email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'bank.account_name' => ['nullable', 'string', 'max:100'],
            'bank.bank_name' => ['nullable', 'string', 'max:100'],
            'bank.account_number' => ['nullable', 'string', 'max:50'],
            'bank.branch' => ['nullable', 'string', 'max:100'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'credit_days' => ['required', 'integer', 'min:1', 'max:365'],
            'tax.enabled' => ['nullable', 'boolean'],
            'tax.name' => ['required_if_accepted:tax.enabled', 'nullable', 'string', 'max:20'],
            'tax.rate' => ['required_if_accepted:tax.enabled', 'nullable', 'numeric', 'min:0', 'max:100'],
            'tax.registration_no' => ['nullable', 'string', 'max:50'],
            'invoice.first_number' => ['required', 'integer', 'min:1'],
            'invoice.default_terms' => ['nullable', 'string', 'max:5000'],
            'invoice.default_note' => ['nullable', 'string', 'max:1000'],
            'quotation.first_number' => ['required', 'integer', 'min:1'],
            'quotation.default_terms' => ['nullable', 'string', 'max:5000'],
            'job.first_number' => ['required', 'integer', 'min:1'],
            'job.default_terms' => ['nullable', 'string', 'max:5000'],
            'backup.keep' => ['required', 'integer', 'min:1', 'max:365'],
            'otp.enabled' => ['nullable', 'boolean'],
            'otp.email' => ['nullable', 'email', 'max:255'],
        ], [
            'company.name.required' => 'Enter your business name.',
            'tax.rate.required_if_accepted' => 'Enter the tax rate, or turn tax off.',
        ]);

        $values = Arr::dot(Arr::except($data, ['logo', 'remove_logo']));
        // Empty boxes come through as null; store them as empty text so they replace the defaults.
        // Browsers send textarea line breaks as \r\n; store plain \n so unchanged text compares equal.
        $values = array_map(fn ($value) => is_string($value) ? str_replace("\r\n", "\n", $value) : ($value ?? ''), $values);
        $values['tax.enabled'] = $request->boolean('tax.enabled');
        $values['otp.enabled'] = $request->boolean('otp.enabled');
        // The rate may be missing when tax is switched off; keep the saved rate rather than resetting it
        if (array_key_exists('tax.rate', $values)) {
            $values['tax.rate'] = (float) ($values['tax.rate'] ?: 0);
        }
        foreach (['credit_days', 'invoice.first_number', 'quotation.first_number', 'job.first_number', 'backup.keep'] as $integer) {
            $values[$integer] = (int) $values[$integer];
        }

        if ($request->hasFile('logo')) {
            $values['company.logo'] = $this->storeLogo($request->file('logo')->getRealPath(), $request->file('logo')->extension());
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteUploadedLogo();
            $values['company.logo'] = 'assets/img/default-logo.png';
        }

        $before = Arr::dot(config('sjpos'));
        $before['company.address'] = config('sjpos.company.address_multiline');
        $changes = collect($values)
            ->filter(fn ($value, string $key) => ($before[$key] ?? null) != $value)
            ->map(fn ($value, string $key) => [Str::limit((string) ($before[$key] ?? ''), 60), Str::limit((string) $value, 60)])
            ->all();

        $settings->save($values);

        if ($changes !== []) {
            ActivityLog::record('updated', 'Business settings', 'settings', null, $changes);
        }

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }

    private function storeLogo(string $tempPath, string $extension): string
    {
        $this->deleteUploadedLogo();
        File::ensureDirectoryExists($this->tenancy->storagePath(self::LOGO_DIR));

        $relative = self::LOGO_DIR.'/logo-'.Str::lower(Str::random(8)).'.'.$extension;
        File::copy($tempPath, $this->tenancy->storagePath($relative));

        return $relative;
    }

    /**
     * Remove the previous uploaded logo: from the business's storage, or from public/uploads
     * for a logo uploaded before tenancy. Never the built-in images under assets/.
     */
    private function deleteUploadedLogo(): void
    {
        $current = (string) config('sjpos.company.logo');
        $path = match (true) {
            str_starts_with($current, self::LOGO_DIR.'/') => $this->tenancy->storagePath($current),
            str_starts_with($current, self::LEGACY_LOGO_DIR.'/') => public_path($current),
            default => null,
        };

        if ($path !== null && is_file($path)) {
            File::delete($path);
        }
    }
}

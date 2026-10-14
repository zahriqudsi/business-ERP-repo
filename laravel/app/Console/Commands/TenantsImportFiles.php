<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\Settings;
use App\Services\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Brings a single-business install's files and .env sign-in settings into a registered business.
 * Everything is copied, never moved, so the old install keeps working if the move is rolled back.
 */
class TenantsImportFiles extends Command
{
    protected $signature = 'tenants:import-files {slug : The business, registered with tenants:register-existing}
        {--dry-run : Only list what would be copied}';

    protected $description = 'Copy the old install\'s uploaded logo, backups, stored files and sign-in settings into a business';

    public function handle(TenantManager $tenancy, Settings $settings): int
    {
        $tenant = Tenant::where('slug', $this->argument('slug'))->first();
        if (! $tenant) {
            $this->error("No business with the subdomain '{$this->argument('slug')}'.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry run: nothing will be copied or saved.');
        }

        // The old install's sign-in settings, read before switching: inside the business only its own settings apply
        $installSignIn = ['otp.enabled' => (bool) config('sjpos.otp.enabled'), 'otp.email' => (string) config('sjpos.otp.email')];

        $tenancy->run($tenant, function () use ($tenancy, $settings, $dryRun, $installSignIn) {
            $this->importLogo($tenancy, $settings, $dryRun);
            $this->copyFiles(storage_path('app/backups'), $tenancy->storagePath('backups'), 'Backups', $dryRun);
            $this->copyFiles(storage_path('app/private'), $tenancy->storagePath('private'), 'Stored files (e.g. quotation PDFs)', $dryRun);
            $this->importSignInSettings($settings, $installSignIn, $dryRun);
        });

        $this->info($dryRun ? 'Dry run finished.' : "Files imported into '{$tenant->slug}'.");

        return self::SUCCESS;
    }

    /**
     * A logo uploaded to public/uploads becomes the business's own branding/ logo.
     * Built-in images under assets/ are shared files and stay as they are.
     */
    private function importLogo(TenantManager $tenancy, Settings $settings, bool $dryRun): void
    {
        $logo = (string) config('sjpos.company.logo');
        if (! str_starts_with($logo, 'uploads/')) {
            $this->line("Logo: {$logo} (nothing to copy)");

            return;
        }
        if (! is_file(public_path($logo))) {
            $this->warn("Logo: {$logo} is missing from public/; skipped");

            return;
        }

        $target = 'branding/'.basename($logo);
        $this->line("Logo: public/{$logo} -> {$target}");
        if ($dryRun) {
            return;
        }

        // Also where a single-business install looks for branding/ logos, so switching tenancy off keeps the logo
        foreach ([$tenancy->storagePath($target), storage_path('app/'.$target)] as $path) {
            File::ensureDirectoryExists(dirname($path));
            File::copy(public_path($logo), $path);
        }
        $settings->save(['company.logo' => $target]);
    }

    private function copyFiles(string $from, string $to, string $label, bool $dryRun): void
    {
        if (! is_dir($from)) {
            $this->line("{$label}: none");

            return;
        }

        $copied = 0;
        $skipped = 0;
        foreach (File::allFiles($from) as $file) {
            $target = $to.DIRECTORY_SEPARATOR.$file->getRelativePathname();
            if (is_file($target)) {
                $skipped++;

                continue;
            }
            if (! $dryRun) {
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            }
            $copied++;
        }

        $this->line("{$label}: {$copied} copied".($skipped ? ", {$skipped} already there" : ''));
    }

    /**
     * The install's OTP_ENABLED / OTP_EMAIL become the business's own Sign-in settings, unless already saved.
     *
     * @param  array{'otp.enabled': bool, 'otp.email': string}  $installSignIn
     */
    private function importSignInSettings(Settings $settings, array $installSignIn, bool $dryRun): void
    {
        $saved = $settings->saved();
        $values = collect($installSignIn)
            ->reject(fn ($value, string $key) => array_key_exists($key, $saved))
            ->all();

        if ($values === []) {
            $this->line('Sign-in settings: already saved for this business');

            return;
        }

        $this->line('Sign-in settings: '.collect($values)->map(fn ($value, $key) => $key.'='.var_export($value, true))->implode(', '));
        if (! $dryRun) {
            $settings->save($values);
        }
    }
}

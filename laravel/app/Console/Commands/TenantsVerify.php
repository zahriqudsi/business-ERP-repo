<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\Landlord\Tenant;
use App\Models\Quotation;
use App\Models\User;
use App\Services\DocumentPdf;
use App\Services\Settings;
use App\Services\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The go/no-go check before and after moving a business: is its database complete, consistent and usable?
 * With --compare, every table's rows and the money totals must match the original database.
 */
class TenantsVerify extends Command
{
    protected $signature = 'tenants:verify {slug : The business}
        {--compare= : The original database (same server) to compare row counts and totals against}';

    protected $description = 'Check a business database before and after a move (exits non-zero on any failure)';

    private const OK = '<info>OK</info>';

    private const WARN = '<comment>WARN</comment>';

    private const FAIL = '<error>FAIL</error>';

    /**
     * Money columns whose totals must survive a move unchanged.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const MONEY = [
        ['invoices', 'subtotal'], ['invoices', 'amount_paid'], ['quotations', 'subtotal'],
        ['purchases', 'subtotal'], ['purchases', 'amount_paid'], ['vendor_bills', 'amount'],
        ['payments_received', 'amount'], ['payments_made', 'amount'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private array $rows = [];

    public function handle(TenantManager $tenancy, Migrator $migrator, Settings $settings, DocumentPdf $pdfs): int
    {
        $tenant = Tenant::where('slug', $this->argument('slug'))->first();
        if (! $tenant) {
            $this->error("No business with the subdomain '{$this->argument('slug')}'.");

            return self::FAILURE;
        }

        $tenancy->run($tenant, function () use ($migrator, $settings, $pdfs) {
            $this->checkMigrations($migrator);
            $this->checkSetup($settings);
            $this->checkConsistency();
            $this->checkPdf($pdfs);
            $this->showNextNumbers();

            if ($source = $this->option('compare')) {
                $this->compareWith((string) $source);
            }
        });

        $this->line("<info>{$tenant->name}</info> ({$tenant->slug}, database {$tenant->database})");
        $this->table(['Check', 'Result', 'Status'], $this->rows);

        $failures = collect($this->rows)->where(2, self::FAIL)->count();
        $failures === 0 ? $this->info('No failures.') : $this->error("{$failures} check(s) failed.");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function checkMigrations(Migrator $migrator): void
    {
        $migrator->getRepository()->setSource(config('database.default'));
        $pending = array_diff(array_keys($migrator->getMigrationFiles([database_path('migrations')])), $migrator->getRepository()->getRan());

        $this->add('Migrations', $pending === [] ? 'all ran' : count($pending).' pending: run tenants:migrate', $pending === [] ? self::OK : self::FAIL);
    }

    private function checkSetup(Settings $settings): void
    {
        $this->add('Business details', $settings->isConfigured() ? (string) config('sjpos.company.name') : 'not saved', $settings->isConfigured() ? self::OK : self::FAIL);

        $logo = (string) config('sjpos.company.logo');
        $logoPath = logo_path();
        $hasOwnLogo = $logoPath !== public_path('assets/img/default-logo.png');
        $this->add('Logo', $hasOwnLogo ? $logo : "default (configured: {$logo})", $hasOwnLogo ? self::OK : self::WARN);

        $owners = User::where('role', 'owner')->where('is_active', true)->count();
        $this->add('Active owners', (string) $owners, $owners > 0 ? self::OK : self::FAIL);

        if (config('sjpos.otp.enabled')) {
            $withoutEmail = User::where('is_active', true)->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))->count();
            $fallback = (string) config('sjpos.otp.email');
            $this->add(
                'Sign-in codes',
                $withoutEmail === 0 ? 'every user has an email' : "{$withoutEmail} user(s) without email".($fallback !== '' ? " use {$fallback}" : ' cannot sign in'),
                $withoutEmail === 0 ? self::OK : ($fallback !== '' ? self::WARN : self::FAIL),
            );
        }
    }

    /**
     * Mismatches here usually come from the legacy app (e.g. its half-payment bug), not from the move: warnings.
     */
    private function checkConsistency(): void
    {
        $paidMismatch = DB::table('invoices')
            ->whereRaw('ROUND(amount_paid, 2) <> ROUND((SELECT COALESCE(SUM(amount), 0) FROM payments_received WHERE payments_received.invoice_id = invoices.id), 2)')
            ->count();
        $this->add('Invoice paid = payments', $paidMismatch === 0 ? 'all match' : "{$paidMismatch} invoice(s) differ", $paidMismatch === 0 ? self::OK : self::WARN);

        $stockMismatch = DB::table('products')
            ->whereRaw('stock_qty <> (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id)')
            ->count();
        $this->add('Stock = movements', $stockMismatch === 0 ? 'all match' : "{$stockMismatch} product(s) differ", $stockMismatch === 0 ? self::OK : self::WARN);
    }

    private function checkPdf(DocumentPdf $pdfs): void
    {
        $invoice = Invoice::latest('id')->first();
        if (! $invoice) {
            $this->add('Invoice PDF', 'no invoices yet', self::WARN);

            return;
        }

        try {
            $renders = str_starts_with($pdfs->invoice($invoice)[1]->output(), '%PDF');
            $this->add('Invoice PDF', "invoice #{$invoice->invoice_no}", $renders ? self::OK : self::FAIL);
        } catch (Throwable $e) {
            $this->add('Invoice PDF', $e->getMessage(), self::FAIL);
        }
    }

    private function showNextNumbers(): void
    {
        $this->add('Next numbers', 'invoice '.Invoice::nextNumber().', quotation '.Quotation::nextNumber().', job '.JobNote::nextNumber(), self::OK);
    }

    /**
     * Same server, so the original is reached by cloning the business connection with another database name.
     */
    private function compareWith(string $sourceDatabase): void
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $sourceDatabase)) {
            $this->add('Compare', "unsafe database name {$sourceDatabase}", self::FAIL);

            return;
        }

        $default = config('database.default');
        config(['database.connections.verify_source' => ['database' => $sourceDatabase] + config("database.connections.{$default}")]);
        DB::purge('verify_source');
        $source = DB::connection('verify_source');
        $target = DB::connection();

        if ($source->getDatabaseName() === $target->getDatabaseName()) {
            $this->add('Compare', 'the business already uses this database (registered as it is): nothing to compare', self::WARN);

            return;
        }

        $sourceTables = $this->tables($source);
        $targetTables = $this->tables($target);

        // The move only adds tables and migration records, so these may differ
        foreach (array_diff($sourceTables, ['migrations']) as $table) {
            if (! in_array($table, $targetTables, true)) {
                $this->add("Rows: {$table}", 'table missing', self::FAIL);

                continue;
            }
            $before = $source->table($table)->count();
            $after = $target->table($table)->count();
            $this->add("Rows: {$table}", $before === $after ? (string) $after : "{$before} before, {$after} now", $before === $after ? self::OK : self::FAIL);
        }

        foreach (self::MONEY as [$table, $column]) {
            if (! in_array($table, $sourceTables, true)) {
                continue;
            }
            $before = round((float) $source->table($table)->sum($column), 2);
            $after = round((float) $target->table($table)->sum($column), 2);
            $this->add("Total: {$table}.{$column}", $before === $after ? rs($after) : rs($before).' before, '.rs($after).' now', $before === $after ? self::OK : self::FAIL);
        }

        DB::purge('verify_source');
    }

    /**
     * @return list<string>
     */
    private function tables(Connection $connection): array
    {
        return collect($connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->all();
    }

    private function add(string $check, string $result, string $status): void
    {
        $this->rows[] = [$check, $result, $status];
    }
}

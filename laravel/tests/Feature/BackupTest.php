<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test backups out of the real storage folder
        $this->storage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sjpos-backup-test-'.uniqid();
        File::ensureDirectoryExists($this->storage);
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    private function contents(string $name): string
    {
        $path = app(DatabaseBackup::class)->path($name);

        return str_ends_with($name, '.gz') ? gzdecode(file_get_contents($path)) : file_get_contents($path);
    }

    public function test_owner_creates_downloads_and_deletes_a_backup(): void
    {
        $this->signIn();
        Invoice::create(['invoice_no' => '1500', 'customer_name' => "O'Brien & Co", 'date' => '2026-10-01', 'subtotal' => 100]);

        $this->post('/backups')->assertRedirect()->assertSessionHas('success');

        $backup = app(DatabaseBackup::class)->list()->first();
        $this->assertNotNull($backup);
        $sql = $this->contents($backup['name']);
        $this->assertStringContainsString('CREATE TABLE `invoices`', $sql);
        $this->assertStringContainsString("'O\\'Brien & Co'", $sql);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS = 1;', $sql);

        $this->get('/backups')->assertOk()->assertSee($backup['name']);
        $this->get('/backups/'.$backup['name'])->assertOk()->assertDownload($backup['name']);

        $this->delete('/backups/'.$backup['name'])->assertRedirect();
        $this->assertTrue(app(DatabaseBackup::class)->list()->isEmpty());
        $this->get('/backups/'.$backup['name'])->assertNotFound();
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        config(['sjpos.backup.keep' => 2]);
        $backups = app(DatabaseBackup::class);
        File::ensureDirectoryExists($backups->directory());
        foreach (['2026-01-01_000000', '2026-01-02_000000', '2026-01-03_000000'] as $stamp) {
            File::put($backups->directory()."/backup-{$stamp}.sql", '-- old');
        }

        $new = $backups->create();

        $this->assertSame([$new, 'backup-2026-01-03_000000.sql'], $backups->list()->pluck('name')->all());
    }

    public function test_command_and_permissions(): void
    {
        $this->artisan('sjpos:backup')->assertSuccessful();
        $this->assertCount(1, app(DatabaseBackup::class)->list());

        $this->signIn('manager');
        $this->get('/backups')->assertForbidden();
        $this->post('/backups')->assertForbidden();
    }

    public function test_file_names_outside_the_backup_pattern_are_rejected(): void
    {
        $this->signIn();

        $this->get('/backups/..%2F.env')->assertNotFound();
        $this->get('/backups/secret.txt')->assertNotFound();
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer and supplier records. Documents keep their printed name column and gain a nullable link,
 * which is backfilled here from the names already on existing documents.
 */
return new class extends Migration
{
    /**
     * Name column on each document table => the party table it links to.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const LINKS = [
        'invoices' => ['customer_name', 'customer_id', 'customers'],
        'quotations' => ['prepared_for', 'customer_id', 'customers'],
        'job_notes' => ['customer_name', 'customer_id', 'customers'],
        'payments_received' => ['customer_name', 'customer_id', 'customers'],
        'purchases' => ['supplier_name', 'supplier_id', 'suppliers'],
        'vendor_bills' => ['supplier_name', 'supplier_id', 'suppliers'],
        'payments_made' => ['supplier_name', 'supplier_id', 'suppliers'],
    ];

    public function up(): void
    {
        // The legacy quotation tables are MyISAM, which ignores foreign keys and transactions
        if (DB::getDriverName() === 'mysql') {
            foreach (['quotations', 'quotation_items'] as $tableName) {
                $engine = DB::table('information_schema.TABLES')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())->where('TABLE_NAME', $tableName)->value('ENGINE');
                if ($engine !== 'InnoDB') {
                    DB::statement("ALTER TABLE {$tableName} ENGINE=InnoDB");
                }
            }
        }

        foreach (['customers', 'suppliers'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255)->unique();
                $table->string('contact_person', 255)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        foreach (self::LINKS as $documentTable => [, $foreignKey, $partyTable]) {
            Schema::table($documentTable, function (Blueprint $table) use ($foreignKey, $partyTable) {
                $table->unsignedInteger($foreignKey)->nullable()->index();
                $table->foreign($foreignKey)->references('id')->on($partyTable)->nullOnDelete();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        foreach (self::LINKS as $documentTable => [, $foreignKey]) {
            Schema::table($documentTable, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign([$foreignKey]);
                $table->dropColumn($foreignKey);
            });
        }

        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
    }

    /**
     * One party per distinct (trimmed, case-insensitive) name, then link every document to it.
     */
    private function backfill(): void
    {
        $now = now();

        foreach (self::LINKS as $documentTable => [$nameColumn, $foreignKey, $partyTable]) {
            $names = DB::table($documentTable)
                ->whereRaw("TRIM(COALESCE({$nameColumn}, '')) <> ''")
                ->distinct()
                ->pluck($nameColumn)
                ->map(fn (string $name) => trim($name));

            $existing = DB::table($partyTable)->pluck('name')->map(fn (string $name) => mb_strtolower($name))->flip();

            $rows = [];
            foreach ($names as $name) {
                $key = mb_strtolower($name);
                if (! $existing->has($key)) {
                    $existing->put($key, true);
                    $rows[] = ['name' => mb_substr($name, 0, 255), 'created_at' => $now, 'updated_at' => $now];
                }
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table($partyTable)->insert($chunk);
            }

            // MySQL's default collation compares names case-insensitively, matching the de-duplication above
            DB::table($documentTable)
                ->join($partyTable, $partyTable.'.name', '=', DB::raw("TRIM({$documentTable}.{$nameColumn})"))
                ->update([$documentTable.'.'.$foreignKey => DB::raw($partyTable.'.id')]);
        }

        // Use the newest job note's contact details for customers that came from job notes
        $latestJobs = DB::table('job_notes')->whereNotNull('customer_id')->orderBy('id')->get(['customer_id', 'contact_person', 'contact_no', 'email']);
        foreach ($latestJobs->keyBy('customer_id') as $customerId => $job) {
            DB::table('customers')->where('id', $customerId)->update(array_filter([
                'contact_person' => $job->contact_person,
                'phone' => $job->contact_no,
                'email' => $job->email,
            ]));
        }
    }
};

<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Full SQL backups of the app's database, written in PHP so they work on servers without mysqldump.
 * Files restore with phpMyAdmin (Import) or `mysql database < file.sql`.
 */
class DatabaseBackup
{
    private const ROWS_PER_INSERT = 200;

    public function directory(): string
    {
        return storage_path('app/backups');
    }

    /**
     * Write a new backup and remove the oldest beyond the "keep" setting.
     *
     * @return string the file name
     */
    public function create(): string
    {
        File::ensureDirectoryExists($this->directory());

        $compress = function_exists('gzopen');
        $name = 'backup-'.now()->format('Y-m-d_His').'.sql'.($compress ? '.gz' : '');
        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        $handle = $compress ? gzopen($path, 'wb6') : fopen($path, 'wb');
        $write = fn (string $text) => $compress ? gzwrite($handle, $text) : fwrite($handle, $text);

        try {
            $this->dump($write);
        } finally {
            $compress ? gzclose($handle) : fclose($handle);
        }

        $this->prune((int) config('sjpos.backup.keep', 14));

        return $name;
    }

    /**
     * @return Collection<int, array{name: string, size: int, created_at: Carbon}>
     */
    public function list(): Collection
    {
        if (! is_dir($this->directory())) {
            return collect();
        }

        return collect(File::files($this->directory()))
            ->filter(fn ($file) => preg_match('/^backup-[0-9_-]+\.sql(\.gz)?$/', $file->getFilename()))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('name')
            ->values();
    }

    public function path(string $name): ?string
    {
        $path = $this->directory().DIRECTORY_SEPARATOR.basename($name);

        return is_file($path) ? $path : null;
    }

    public function delete(string $name): void
    {
        if ($path = $this->path($name)) {
            File::delete($path);
        }
    }

    private function prune(int $keep): void
    {
        $this->list()->slice(max(1, $keep))->each(fn (array $backup) => $this->delete($backup['name']));
    }

    /**
     * @param  callable(string): mixed  $write
     */
    private function dump(callable $write): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();

        $write("-- {$database} backup made ".now()->toDateTimeString().' by '.config('sjpos.product_name')."\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = collect(DB::select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
            ->map(fn ($row) => array_values((array) $row)[0]);

        foreach ($tables as $table) {
            $create = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))[1];
            $write("DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");

            $columns = null;
            $batch = [];
            foreach (DB::table($table)->cursor() as $row) {
                $row = (array) $row;
                $columns ??= '`'.implode('`, `', array_keys($row)).'`';
                $batch[] = '('.implode(', ', array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), $row)).')';

                if (count($batch) === self::ROWS_PER_INSERT) {
                    $write("INSERT INTO `{$table}` ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $write("INSERT INTO `{$table}` ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
            }
            $write("\n");
        }

        $write("SET FOREIGN_KEY_CHECKS = 1;\n");
    }
}
